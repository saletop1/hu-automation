from flask import Flask, request, jsonify
from flask_cors import CORS
from pyrfc import Connection
import os
import logging
import pymysql
from datetime import datetime
from decimal import Decimal
import sys
import io
from apscheduler.schedulers.background import BackgroundScheduler
import atexit
import traceback

# Fix encoding untuk Windows
if sys.platform.startswith('win'):
    if sys.stdout.encoding != 'utf-8':
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    if sys.stderr.encoding != 'utf-8':
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

app = Flask(__name__)
CORS(app)

# Setup logging - lebih minimalis
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler(), logging.FileHandler('sap_hu.log', encoding='utf-8')]
)
logger = logging.getLogger(__name__)

# Konfigurasi database
DB_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'sap_hu_automation'),
    'charset': 'utf8mb4'
}

# Environment variables SAP
os.environ['SAP_USER'] = os.getenv('SAP_USER', 'auto_email')
os.environ['SAP_PASSWORD'] = os.getenv('SAP_PASSWORD', '11223344')
os.environ['SAP_ASHOST'] = os.getenv('SAP_ASHOST', '192.168.254.154')
os.environ['SAP_SYSNR'] = os.getenv('SAP_SYSNR', '01')
os.environ['SAP_CLIENT'] = os.getenv('SAP_CLIENT', '300')

# Scheduler untuk auto sync
scheduler = BackgroundScheduler()

# Status sync terakhir
last_sync_status = {
    'last_success_time': None,
    'last_attempt_time': None,
    'last_error': None,
    'is_running': False
}

def update_sync_status(success=True, error=None):
    """Update status sync terakhir - VERSION FIXED"""
    last_sync_status['last_attempt_time'] = datetime.now()
    last_sync_status['is_running'] = False  # ✅ SELALU RESET KE FALSE

    if success:
        last_sync_status['last_success_time'] = datetime.now()
        last_sync_status['last_error'] = None
    else:
        last_sync_status['last_error'] = error

def auto_sync_job():
    """Job untuk auto sync setiap 30 menit - VERSION FIXED"""
    if last_sync_status['is_running']:
        logger.info("Auto sync ditunda karena proses sync sedang berjalan")
        return

    # SET STATUS RUNNING
    last_sync_status['is_running'] = True
    last_sync_status['last_attempt_time'] = datetime.now()

    logger.info("Auto sync dimulai...")

    try:
        success = sync_stock_data('3000', '3D10')

        if success:
            logger.info("Auto sync selesai")
            last_sync_status['last_success_time'] = datetime.now()
            last_sync_status['is_running'] = False  # ✅ RESET
        else:
            logger.error("Auto sync gagal")
            last_sync_status['last_error'] = "Auto sync gagal"
            last_sync_status['is_running'] = False  # ✅ RESET

    except Exception as e:
        error_msg = f"Error dalam auto sync: {e}"
        logger.error(error_msg)
        last_sync_status['last_error'] = error_msg
        last_sync_status['is_running'] = False  # ✅ RESET

def start_scheduler():
    """Mulai scheduler untuk auto sync"""
    try:
        scheduler.add_job(
            func=auto_sync_job,
            trigger='interval',
            minutes=30,
            id='auto_sync_job',
            replace_existing=True
        )
        scheduler.start()
        logger.info("Scheduler started - Auto sync setiap 30 menit")
    except Exception as e:
        logger.error(f"Gagal start scheduler: {e}")

def stop_scheduler():
    """Stop scheduler saat aplikasi berhenti"""
    if scheduler.running:
        scheduler.shutdown()
        logger.info("Scheduler dihentikan")

atexit.register(stop_scheduler)

# Fungsi dasar
def connect_sap():
    """Buka koneksi ke SAP dengan logging lebih detail"""
    try:
        sap_user = os.getenv("SAP_USER", "auto_email")
        sap_password = os.getenv("SAP_PASSWORD", "11223344")
        sap_ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sap_sysnr = os.getenv("SAP_SYSNR", "01")
        sap_client = os.getenv("SAP_CLIENT", "300")

        conn = Connection(
            user=sap_user,
            passwd=sap_password,
            ashost=sap_ashost,
            sysnr=sap_sysnr,
            client=sap_client,
            lang="EN",
        )

        # Test koneksi dengan ping
        conn.ping()
        logger.info("Koneksi SAP berhasil")
        return conn

    except Exception as e:
        logger.error(f"Gagal koneksi SAP: {str(e)}")
        return None

def connect_mysql():
    """Buka koneksi ke MySQL"""
    try:
        conn = pymysql.connect(**DB_CONFIG)
        return conn
    except Exception as e:
        logger.error(f"Gagal koneksi MySQL: {e}")
        return None

def clean_value(value):
    """Bersihkan data dari SAP"""
    if value is None:
        return ''
    return str(value).strip()

def convert_qty(value):
    """Konversi quantity ke Decimal"""
    try:
        if not value:
            return Decimal('0')
        return Decimal(str(value)).quantize(Decimal('0.001'))
    except:
        return Decimal('0')

def ensure_magry_column_exists():
    """Pastikan kolom magry ada di tabel stock_data"""
    mysql_conn = connect_mysql()
    if not mysql_conn:
        return False

    try:
        with mysql_conn.cursor() as cursor:
            cursor.execute("""
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'stock_data'
                AND COLUMN_NAME = 'magry'
            """)
            result = cursor.fetchone()

            if result[0] == 0:
                cursor.execute("ALTER TABLE stock_data ADD COLUMN magry VARCHAR(50) NULL AFTER base_unit")
                mysql_conn.commit()

        return True
    except Exception as e:
        logger.error(f"Error memeriksa kolom magry: {e}")
        return False
    finally:
        mysql_conn.close()

# Sync stock data
def sync_stock_data(plant='3000', storage_location='3D10'):
    """Sync data stock dari SAP ke database"""
    sap_conn = None
    mysql_conn = None

    try:
        sap_conn = connect_sap()
        if not sap_conn:
            logger.error("Tidak dapat melanjutkan sync karena koneksi SAP gagal")
            return False

        # Ambil data dari SAP
        result = sap_conn.call('Z_FM_YMMR006NX',
                             P_WERKS=plant,
                             P_MTART='FERT',
                             P_LGORT=storage_location)

        stock_data = result.get('T_DATA', [])

        if not stock_data:
            logger.info("Tidak ada data dari SAP")
            return True

        mysql_conn = connect_mysql()
        if not mysql_conn:
            logger.error("Koneksi database gagal")
            return False

        inserted = 0
        updated = 0
        sales_data_count = 0

        with mysql_conn.cursor() as cursor:
            for item in stock_data:
                material = clean_value(item.get('MATNR'))
                desc = clean_value(item.get('MAKTX'))
                qty = convert_qty(item.get('CLABS'))
                magrv = clean_value(item.get('MAGRV'))
                sales_document = clean_value(item.get('VBELN'))
                item_number = clean_value(item.get('POSNR'))
                vendor_name = clean_value(item.get('NAME1'))

                if sales_document:
                    sales_data_count += 1

                if not material:
                    continue

                sql = """
                INSERT INTO stock_data
                (material, material_description, plant, storage_location, batch,
                 stock_quantity, base_unit, magry, sales_document, item_number, vendor_name, last_updated)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                material_description = VALUES(material_description),
                stock_quantity = VALUES(stock_quantity),
                magry = VALUES(magry),
                sales_document = VALUES(sales_document),
                item_number = VALUES(item_number),
                vendor_name = VALUES(vendor_name),
                last_updated = VALUES(last_updated)
                """

                cursor.execute(sql, (
                    material, desc, plant, storage_location,
                    clean_value(item.get('CHARG')), float(qty),
                    clean_value(item.get('MEINS')), magrv,
                    sales_document,
                    item_number,
                    vendor_name,
                    datetime.now()
                ))

                if cursor.rowcount == 1:
                    inserted += 1
                else:
                    updated += 1

            mysql_conn.commit()
            logger.info(f"Sync selesai: {inserted} baru, {updated} update")
            logger.info(f"Data sales document tersimpan: {sales_data_count} records")
            return True

    except Exception as e:
        logger.error(f"Error dalam sync_stock_data: {e}")
        if mysql_conn:
            mysql_conn.rollback()
        return False
    finally:
        # PASTIKAN KONEKSI SELALU DITUTUP
        if sap_conn:
            sap_conn.close()
        if mysql_conn:
            mysql_conn.close()

# FUNGSI UTILITY YANG DIPERBAIKI UNTUK HU CREATION
def clean_hu_parameters(hu_data):
    """Bersihkan semua parameter HU dari nilai None dan konversi ke string"""
    cleaned = hu_data.copy()

    # Field yang wajib
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
    for field in required_fields:
        if field not in cleaned:
            raise ValueError(f"Field wajib {field} tidak ditemukan")
        if cleaned[field] is None:
            raise ValueError(f"Field wajib {field} tidak boleh None")

        cleaned[field] = str(cleaned[field]).strip()

        # Format leading zero untuk material dan pack_mat
        if field in ['material', 'pack_mat']:
            if cleaned[field].isdigit():
                cleaned[field] = cleaned[field].zfill(18)

        # Validasi pack_qty
        if field == 'pack_qty':
            try:
                float(cleaned[field])
            except ValueError:
                raise ValueError(f"pack_qty harus berupa angka: {cleaned[field]}")

    # Field optional
    optional_fields = ['batch', 'sp_stck_no', 'base_unit_qty']
    for field in optional_fields:
        value = cleaned.get(field)
        if value is None:
            cleaned[field] = ''
        else:
            cleaned[field] = str(value).strip() if value else ''

    # Validasi HU External ID
    if not cleaned['hu_exid'].isdigit() or len(cleaned['hu_exid']) != 10:
        raise ValueError(f"HU External ID harus 10 digit angka: {cleaned['hu_exid']}")

    return cleaned

def prepare_hu_items(cleaned_data):
    """Siapkan T_ITEMS untuk RFC call"""
    material = cleaned_data['material']
    if material.isdigit() and len(material) < 18:
        material = material.zfill(18)

    batch = cleaned_data.get('batch', '')
    if batch is None:
        batch = ''

    sp_stck_no = cleaned_data.get('sp_stck_no', '')
    if sp_stck_no is None:
        sp_stck_no = ''

    item = {
        "MATERIAL": material,
        "PLANT": cleaned_data['plant'],
        "STGE_LOC": cleaned_data['stge_loc'],
        "PACK_QTY": cleaned_data['pack_qty'],
        "HU_ITEM_TYPE": "1",
        "BATCH": batch,
        "SPEC_STOCK": "E" if sp_stck_no else "",
        "SP_STCK_NO": sp_stck_no
    }

    return [item]

def validate_sap_response(result):
    """Validasi response dari SAP RFC call"""
    if not result:
        return False, "Empty response from SAP"

    # Cek error message dari SAP
    if 'E_RETURN' in result and result['E_RETURN']:
        error_type = result['E_RETURN'].get('TYPE', '')
        error_message = result['E_RETURN'].get('MESSAGE', 'Unknown SAP error')

        if error_type in ['E', 'A']:
            return False, error_message
        elif error_type == 'W':
            return True, error_message

    # Cek apakah HU number berhasil dibuat
    if result.get('E_HU_EXID'):
        return True, f"HU created successfully: {result['E_HU_EXID']}"
    else:
        return False, "No HU number returned from SAP"

# Create HU functions - VERSI YANG DIPERBAIKI
def create_single_hu(data):
    """Skenario 1: 1 HU dengan 1 Material"""
    # Validasi data wajib
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
    for field in required_fields:
        if field not in data:
            return {"success": False, "error": f"Field wajib {field} tidak ditemukan"}

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        cleaned_data = clean_hu_parameters(data)

        params = {
            "I_HU_EXID": cleaned_data['hu_exid'],
            "I_PACK_MAT": cleaned_data['pack_mat'],
            "I_PLANT": cleaned_data['plant'],
            "I_STGE_LOC": cleaned_data['stge_loc'],
            "T_ITEMS": prepare_hu_items(cleaned_data)
        }

        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        # Validasi response
        success, message = validate_sap_response(result)

        if success:
            hu_number = result.get('E_HU_EXID', cleaned_data['hu_exid'])
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number
            }
        else:
            return {"success": False, "error": message}

    except ValueError as e:
        error_msg = f"Data tidak valid: {str(e)}"
        return {"success": False, "error": error_msg}
    except Exception as e:
        error_msg = f"Error buat HU: {str(e)}"
        return {"success": False, "error": error_msg}
    finally:
        if sap_conn:
            sap_conn.close()

def create_single_multi_hu(data):
    """Skenario 2: 1 HU dengan Multiple Material"""
    # Validasi data wajib
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'items']
    for field in required_fields:
        if not data.get(field):
            return {"success": False, "error": f"Field {field} tidak boleh kosong"}

    if not isinstance(data['items'], list) or len(data['items']) == 0:
        return {"success": False, "error": "Items harus berupa array tidak kosong"}

    # Format manual pack_mat sebelum diproses
    original_pack_mat = data['pack_mat']
    if original_pack_mat.isdigit() and len(original_pack_mat) < 18:
        data['pack_mat'] = original_pack_mat.zfill(18)

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        main_data = clean_hu_parameters({
            'hu_exid': data['hu_exid'],
            'pack_mat': data['pack_mat'],
            'plant': data['plant'],
            'stge_loc': data['stge_loc'],
            'material': 'DUMMY',
            'pack_qty': '1',
            'batch': '',
            'sp_stck_no': ''
        })

        items = []
        for i, item in enumerate(data['items']):
            try:
                if 'material' not in item or 'pack_qty' not in item:
                    return {"success": False, "error": f"Item {i+1}: material dan pack_qty wajib diisi"}

                # Format manual material item sebelum diproses
                original_material = item.get('material')
                if original_material and original_material.isdigit() and len(original_material) < 18:
                    item['material'] = original_material.zfill(18)

                cleaned_item = clean_hu_parameters({
                    'hu_exid': main_data['hu_exid'],
                    'pack_mat': main_data['pack_mat'],
                    'plant': main_data['plant'],
                    'stge_loc': main_data['stge_loc'],
                    'material': item.get('material'),
                    'pack_qty': item.get('pack_qty'),
                    'batch': item.get('batch', ''),
                    'sp_stck_no': item.get('sp_stck_no', '')
                })

                items.append({
                    "MATERIAL": cleaned_item['material'],
                    "PLANT": cleaned_item['plant'],
                    "STGE_LOC": cleaned_item['stge_loc'],
                    "PACK_QTY": cleaned_item['pack_qty'],
                    "HU_ITEM_TYPE": "1",
                    "BATCH": cleaned_item['batch'],
                    "SPEC_STOCK": "E" if cleaned_item['sp_stck_no'] else "",
                    "SP_STCK_NO": cleaned_item['sp_stck_no']
                })

            except ValueError as e:
                return {"success": False, "error": f"Item {i+1}: {str(e)}"}

        params = {
            "I_HU_EXID": main_data['hu_exid'],
            "I_PACK_MAT": main_data['pack_mat'],
            "I_PLANT": main_data['plant'],
            "I_STGE_LOC": main_data['stge_loc'],
            "T_ITEMS": items
        }

        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        # Validasi response
        success, message = validate_sap_response(result)

        if success:
            hu_number = result.get('E_HU_EXID', main_data['hu_exid'])
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number
            }
        else:
            return {"success": False, "error": message}

    except Exception as e:
        error_msg = f"Error buat HU multi: {str(e)}"
        return {"success": False, "error": error_msg}
    finally:
        if sap_conn:
            sap_conn.close()

def create_multiple_hus(data):
    """Skenario 3: Multiple HU (Setiap HU 1 Material)"""
    if 'hus' not in data or not isinstance(data['hus'], list):
        return {"success": False, "error": "Data 'hus' harus berupa array"}

    if len(data['hus']) == 0:
        return {"success": False, "error": "Data 'hus' tidak boleh kosong"}

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        results = []
        success_count = 0

        for i, hu_data in enumerate(data['hus']):
            try:
                logger.info(f"Memproses HU {i+1}/{len(data['hus'])}: {hu_data.get('hu_exid', 'UNKNOWN')}")

                # Validasi data wajib untuk setiap HU
                required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
                for field in required_fields:
                    if field not in hu_data:
                        results.append({
                            "hu_exid": hu_data.get('hu_exid', f"HU_{i+1}"),
                            "success": False,
                            "error": f"Field {field} tidak ditemukan"
                        })
                        continue

                cleaned_data = clean_hu_parameters(hu_data)

                params = {
                    "I_HU_EXID": cleaned_data['hu_exid'],
                    "I_PACK_MAT": cleaned_data['pack_mat'],
                    "I_PLANT": cleaned_data['plant'],
                    "I_STGE_LOC": cleaned_data['stge_loc'],
                    "T_ITEMS": prepare_hu_items(cleaned_data)
                }

                # Panggil RFC SAP
                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                # Validasi response
                success, message = validate_sap_response(result)

                if success:
                    hu_number = result.get('E_HU_EXID', cleaned_data['hu_exid'])
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": True,
                        "message": message,
                        "data": result,
                        "created_hu": hu_number
                    })
                    success_count += 1
                else:
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": False,
                        "error": message
                    })

            except ValueError as e:
                error_msg = f"Data tidak valid: {str(e)}"
                results.append({
                    "hu_exid": hu_data.get('hu_exid', f'HU_{i+1}'),
                    "success": False,
                    "error": error_msg
                })
            except Exception as e:
                error_msg = f"Error processing HU: {str(e)}"
                results.append({
                    "hu_exid": hu_data.get('hu_exid', f'HU_{i+1}'),
                    "success": False,
                    "error": error_msg
                })

        logger.info(f"Total HU: {len(results)}, Berhasil: {success_count}")
        return {
            "success": success_count > 0,
            "message": f"Processed {len(results)} HUs, {success_count} successful, {len(results)-success_count} failed",
            "data": results,
            "summary": {
                "total": len(results),
                "success": success_count,
                "failed": len(results) - success_count
            }
        }

    except Exception as e:
        error_msg = f"Error buat multiple HU: {str(e)}"
        return {"success": False, "error": error_msg}
    finally:
        if sap_conn:
            sap_conn.close()

# Routes API
@app.route('/stock/sync', methods=['POST'])
def api_sync_stock():
    """Sync stock manual dari Laravel"""
    if last_sync_status['is_running']:
        logger.warning("Sync ditolak - status masih running")
        return jsonify({"success": False, "error": "Sync sedang berjalan, coba lagi nanti"}), 429

    data = request.get_json() or {}
    plant = data.get('plant', '3000')
    storage_location = data.get('storage_location', '3D10')

    # Set status running SEBELUM proses
    last_sync_status['is_running'] = True
    last_sync_status['last_attempt_time'] = datetime.now()
    last_sync_status['last_error'] = None

    try:
        logger.info(f"Manual sync dimulai - Plant: {plant}, Lokasi: {storage_location}")
        success = sync_stock_data(plant, storage_location)

        if success:
            last_sync_status['last_success_time'] = datetime.now()
            last_sync_status['is_running'] = False  # ✅ RESET STATUS
            logger.info("Manual sync selesai dengan sukses")
            return jsonify({
                "success": True,
                "message": "Sync berhasil",
                "last_sync": datetime.now().isoformat()
            })
        else:
            last_sync_status['last_error'] = "Sync manual gagal"
            last_sync_status['is_running'] = False  # ✅ RESET STATUS MESKI GAGAL
            logger.error("Manual sync gagal")
            return jsonify({"success": False, "error": "Sync gagal"}), 500

    except Exception as e:
        error_msg = f"Error dalam sync manual: {e}"
        logger.error(error_msg)
        # ✅ PASTIKAN STATUS DI-RESET MESKIPUN ERROR
        last_sync_status['last_error'] = error_msg
        last_sync_status['is_running'] = False
        return jsonify({"success": False, "error": error_msg}), 500

@app.route('/stock/last-sync', methods=['GET'])
def api_get_last_sync():
    """Dapatkan informasi sync terakhir"""
    return jsonify({
        "success": True,
        "data": {
            "last_success_time": last_sync_status['last_success_time'].isoformat() if last_sync_status['last_success_time'] else None,
            "last_attempt_time": last_sync_status['last_attempt_time'].isoformat() if last_sync_status['last_attempt_time'] else None,
            "last_error": last_sync_status['last_error'],
            "is_running": last_sync_status['is_running']
        }
    })

@app.route('/stock/data', methods=['GET'])
def api_get_stock_data():
    """Dapatkan data stock dari database"""
    mysql_conn = connect_mysql()
    if not mysql_conn:
        return jsonify({"success": False, "error": "Database tidak tersedia"}), 500
    try:
        with mysql_conn.cursor(pymysql.cursors.DictCursor) as cursor:
            cursor.execute("""
                SELECT material, material_description, plant, storage_location,
                       batch, stock_quantity, base_unit, magry, last_updated
                FROM stock_data
                ORDER BY material_description, material
            """)
            data = cursor.fetchall()
            for item in data:
                if item['last_updated']:
                    item['last_updated'] = item['last_updated'].isoformat()
            return jsonify({
                "success": True,
                "data": data,
                "count": len(data)
            })
    except Exception as e:
        logger.error(f"Error mengambil data stock: {e}")
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        mysql_conn.close()

@app.route('/stock/reset-status', methods=['POST'])
def api_reset_sync_status():
    """Reset status sync (untuk emergency)"""
    last_sync_status.update({
        'is_running': False,
        'last_error': None
    })
    return jsonify({
        "success": True,
        "message": "Status sync berhasil di-reset"
    })

@app.route('/hu/create-single', methods=['POST'])
def api_create_single():
    """Buat HU Skenario 1"""
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    result = create_single_hu(data)
    return jsonify(result)

@app.route('/hu/create-single-multi', methods=['POST'])
def api_create_single_multi():
    """Buat HU Skenario 2"""
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    result = create_single_multi_hu(data)
    return jsonify(result)

@app.route('/hu/create-multiple', methods=['POST'])
def api_create_multiple():
    """Buat HU Skenario 3"""
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info(f"Received multiple HU creation request for {len(data.get('hus', []))} HUs")

    # Validasi struktur data
    if 'hus' not in data or not isinstance(data['hus'], list):
        return jsonify({"success": False, "error": "Data 'hus' harus berupa array"}), 400

    if len(data['hus']) == 0:
        return jsonify({"success": False, "error": "Data 'hus' tidak boleh kosong"}), 400

    result = create_multiple_hus(data)
    return jsonify(result)

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "healthy", "service": "SAP HU API"})

@app.route('/test-sap', methods=['GET'])
def test_sap():
    """Endpoint untuk test koneksi SAP"""
    sap_conn = connect_sap()
    if sap_conn:
        sap_conn.close()
        return jsonify({"success": True, "message": "Koneksi SAP berhasil"})
    else:
        return jsonify({"success": False, "error": "Koneksi SAP gagal"}), 500

# Jalankan aplikasi
if __name__ == '__main__':
    logger.info("Starting SAP HU Automation API")

    # Pastikan kolom magry ada
    ensure_magry_column_exists()

    # Test koneksi database
    if connect_mysql():
        logger.info("Database siap")
    else:
        logger.error("Database error - aplikasi tetap berjalan tapi sync akan gagal")

    # Test koneksi SAP
    logger.info("Testing koneksi SAP...")
    sap_conn = connect_sap()
    if sap_conn:
        logger.info("SAP siap")
        sap_conn.close()

        # Jalankan sync pertama
        logger.info("Jalankan sync pertama...")
        try:
            last_sync_status['is_running'] = True
            sync_stock_data()
            update_sync_status(success=True)
        except Exception as e:
            error_msg = f"Sync pertama gagal: {e}"
            logger.error(error_msg)
            update_sync_status(success=False, error=error_msg)
    else:
        logger.warning("SAP tidak tersedia - fitur sync dan create HU akan gagal sampai koneksi pulih")

    # Start scheduler
    start_scheduler()

    # Jalankan Flask server
    logger.info("Starting Flask server di port 5000...")
    try:
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.error(f"Gagal start Flask server: {e}")
    finally:
        stop_scheduler()
