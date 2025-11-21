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

# Setup logging - lebih detail untuk debugging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(levelname)s - %(name)s - %(message)s',
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

# Environment variables SAP (fallback saja)
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
    """Update status sync terakhir"""
    last_sync_status['last_attempt_time'] = datetime.now()
    last_sync_status['is_running'] = False

    if success:
        last_sync_status['last_success_time'] = datetime.now()
        last_sync_status['last_error'] = None
    else:
        last_sync_status['last_error'] = error

def auto_sync_job():
    """Job untuk auto sync setiap 30 menit"""
    if last_sync_status['is_running']:
        logger.info("Auto sync ditunda karena proses sync sedang berjalan")
        return

    last_sync_status['is_running'] = True
    last_sync_status['last_attempt_time'] = datetime.now()

    logger.info("Auto sync dimulai...")

    try:
        success = sync_stock_data('3000', '3D10')

        if success:
            logger.info("Auto sync selesai")
            last_sync_status['last_success_time'] = datetime.now()
            last_sync_status['is_running'] = False
        else:
            logger.error("Auto sync gagal")
            last_sync_status['last_error'] = "Auto sync gagal"
            last_sync_status['is_running'] = False

    except Exception as e:
        error_msg = f"Error dalam auto sync: {e}"
        logger.error(error_msg)
        last_sync_status['last_error'] = error_msg
        last_sync_status['is_running'] = False

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

# ==================== FUNGSI KONEKSI SAP DENGAN CREDENTIALS ====================

def connect_sap_with_credentials(sap_user, sap_password):
    """Buka koneksi ke SAP HANYA dengan credentials spesifik dari request"""
    try:
        # VALIDASI KETAT - tolak credentials yang sama dengan environment/default
        default_user = os.getenv("SAP_USER", "auto_email")
        if not sap_user or sap_user == default_user:
            logger.error(f"❌ SAP User dari request tidak valid atau masih default: {sap_user}")
            return None, "SAP User tidak valid atau masih menggunakan default user"

        if not sap_password:
            logger.error("❌ SAP Password dari request kosong")
            return None, "SAP Password tidak boleh kosong"

        sap_ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sap_sysnr = os.getenv("SAP_SYSNR", "01")
        sap_client = os.getenv("SAP_CLIENT", "300")

        logger.info(f"🔐 Mencoba koneksi SAP dengan user: {sap_user}")
        logger.info(f"🔐 Host: {sap_ashost}, System: {sap_sysnr}, Client: {sap_client}")

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
        logger.info(f"✅ Koneksi SAP berhasil dengan user: {sap_user}")
        return conn, None

    except Exception as e:
        error_msg = f"Gagal koneksi SAP: {str(e)}"
        logger.error(f"❌ {error_msg}")
        return None, error_msg

def connect_sap():
    """Buka koneksi ke SAP dengan environment credentials (untuk sync saja)"""
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

        conn.ping()
        logger.info("✅ Koneksi SAP berhasil (environment credentials)")
        return conn

    except Exception as e:
        logger.error(f"❌ Gagal koneksi SAP: {str(e)}")
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
            return True

    except Exception as e:
        logger.error(f"Error dalam sync_stock_data: {e}")
        if mysql_conn:
            mysql_conn.rollback()
        return False
    finally:
        if sap_conn:
            sap_conn.close()
        if mysql_conn:
            mysql_conn.close()

# FUNGSI UTILITY UNTUK HU CREATION
def clean_hu_parameters(hu_data):
    """Bersihkan semua parameter HU dari nilai None dan konversi ke string"""
    cleaned = hu_data.copy()

    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
    for field in required_fields:
        if field not in cleaned:
            raise ValueError(f"Field wajib {field} tidak ditemukan")
        if cleaned[field] is None:
            raise ValueError(f"Field wajib {field} tidak boleh None")

        cleaned[field] = str(cleaned[field]).strip()

        if field in ['material', 'pack_mat']:
            if cleaned[field].isdigit():
                cleaned[field] = cleaned[field].zfill(18)

        if field == 'pack_qty':
            try:
                float(cleaned[field])
            except ValueError:
                raise ValueError(f"pack_qty harus berupa angka: {cleaned[field]}")

    optional_fields = ['batch', 'sp_stck_no', 'base_unit_qty']
    for field in optional_fields:
        value = cleaned.get(field)
        if value is None:
            cleaned[field] = ''
        else:
            cleaned[field] = str(value).strip() if value else ''

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
    """Validasi response dari SAP RFC call - gunakan E_HUKEY"""
    if not result:
        return False, "Tidak ada response dari SAP RFC call"

    # Cek jika ada error message dari SAP
    if 'E_RETURN' in result and result['E_RETURN']:
        error_type = result['E_RETURN'].get('TYPE', '')
        error_message = result['E_RETURN'].get('MESSAGE', 'Unknown SAP error')

        if error_type in ['E', 'A']:
            return False, error_message
        elif error_type == 'W':
            return True, error_message
        elif error_type == 'S':
            # Success message, lanjut cek HU number
            pass

    # ✅ PERBAIKAN: Gunakan E_HUKEY sebagai nomor HU
    if result.get('E_HUKEY'):
        hu_number = result['E_HUKEY']
        logger.info(f"✅ HU berhasil dibuat: {hu_number}")
        return True, f"HU berhasil dibuat: {hu_number}"
    else:
        # Jika tidak ada E_HUKEY, coba field lain atau return error
        logger.warning(f"⚠️ Tidak ada E_HUKEY dalam response. Fields yang ada: {list(result.keys())}")
        return False, "Gagal membuat HU - sistem SAP tidak mengembalikan nomor HU"

# ==================== CREATE HU FUNCTIONS DENGAN CREDENTIALS DARI REQUEST ====================

def validate_sap_response(result):
    """Validasi response dari SAP RFC call - gunakan E_HUKEY"""
    if not result:
        return False, "Tidak ada response dari SAP RFC call"

    # Cek jika ada error message dari SAP
    if 'E_RETURN' in result and result['E_RETURN']:
        error_type = result['E_RETURN'].get('TYPE', '')
        error_message = result['E_RETURN'].get('MESSAGE', 'Unknown SAP error')

        if error_type in ['E', 'A']:
            return False, error_message
        elif error_type == 'W':
            return True, error_message
        elif error_type == 'S':
            # Success message, lanjut cek HU number
            pass

    # ✅ PERBAIKAN: Gunakan E_HUKEY sebagai nomor HU
    if result.get('E_HUKEY'):
        hu_number = result['E_HUKEY']
        # ✅ HAPUS LEADING ZERO
        hu_number_clean = hu_number.lstrip('0') or '0'  # Jika semua angka nol, tetap tampilkan 0
        logger.info(f"✅ HU berhasil dibuat: {hu_number} -> {hu_number_clean}")
        return True, f"HU berhasil dibuat: {hu_number_clean}"
    else:
        # Jika tidak ada E_HUKEY, coba field lain atau return error
        logger.warning(f"⚠️ Tidak ada E_HUKEY dalam response. Fields yang ada: {list(result.keys())}")
        return False, "Gagal membuat HU - sistem SAP tidak mengembalikan nomor HU"

def create_single_hu(data):
    """Skenario 1: 1 HU dengan 1 Material"""
    logger.info("🟢 MEMULAI CREATE SINGLE HU")
    logger.info(f"📦 Data yang diterima: {list(data.keys())}")

    # ✅ DEBUG: Log semua data yang diterima (kecuali password)
    for key, value in data.items():
        if key != 'sap_password':
            logger.info(f"   {key}: {value}")
        else:
            logger.info(f"   {key}: {'*' * len(value) if value else 'EMPTY'}")

    # ✅ VALIDASI KETAT CREDENTIALS
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty', 'sap_user', 'sap_password']
    for field in required_fields:
        if field not in data or not data[field]:
            error_msg = f"Field wajib {field} tidak ditemukan atau kosong"
            logger.error(f"❌ {error_msg}")
            return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    # ✅ TOLAK CREDENTIALS DEFAULT
    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"🔐 Menggunakan SAP User dari request: {sap_user}")

    # ✅ PERBAIKAN: Tangani error koneksi SAP dengan status code yang tepat
    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 401  # 401 Unauthorized

    try:
        cleaned_data = clean_hu_parameters(data)
        logger.info(f"✅ Data berhasil dibersihkan")

        params = {
            "I_HU_EXID": cleaned_data['hu_exid'],
            "I_PACK_MAT": cleaned_data['pack_mat'],
            "I_PLANT": cleaned_data['plant'],
            "I_STGE_LOC": cleaned_data['stge_loc'],
            "T_ITEMS": prepare_hu_items(cleaned_data)
        }

        logger.info(f"📤 Memanggil RFC ZRFC_CREATE_HU_EXT dengan HU External: {cleaned_data['hu_exid']}")
        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        # ✅ DEBUG: Log semua field yang di-return oleh SAP
        logger.info(f"🔍 Response fields dari SAP: {list(result.keys())}")
        for key, value in result.items():
            if key != 'T_ITEMS':  # Skip table yang besar
                logger.info(f"🔍 {key}: {value}")

        success, message = validate_sap_response(result)

        if success:
            # ✅ PERBAIKAN: Gunakan E_HUKEY dan HAPUS LEADING ZERO
            hu_number = result.get('E_HUKEY')
            hu_number_clean = hu_number.lstrip('0') or '0'  # Hapus leading zero
            logger.info(f"✅ HU berhasil dibuat - HU Key: {hu_number} -> {hu_number_clean}")
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number_clean  # ✅ Kirim tanpa leading zero
            }, 200
        else:
            return {"success": False, "error": message}, 400

    except ValueError as e:
        error_msg = f"Data tidak valid: {str(e)}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400
    except Exception as e:
        error_msg = f"Error buat HU: {str(e)}"
        logger.error(f"❌ {error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()
            logger.info("🔒 Koneksi SAP ditutup")

def create_single_multi_hu(data):
    """Skenario 2: 1 HU dengan Multiple Material"""
    logger.info("🟢 MEMULAI CREATE SINGLE MULTI HU")
    logger.info(f"📦 Data yang diterima: {list(data.keys())}")
    logger.info(f"📦 Jumlah items: {len(data.get('items', []))}")

    # ✅ VALIDASI KETAT CREDENTIALS
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'items', 'sap_user', 'sap_password']
    for field in required_fields:
        if not data.get(field):
            error_msg = f"Field {field} tidak boleh kosong"
            logger.error(f"❌ {error_msg}")
            return {"success": False, "error": error_msg}, 400

    if not isinstance(data['items'], list) or len(data['items']) == 0:
        error_msg = "Items harus berupa array tidak kosong"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    # ✅ TOLAK CREDENTIALS DEFAULT
    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"🔐 Menggunakan SAP User dari request: {sap_user}")

    # ✅ PERBAIKAN: Tangani error koneksi SAP dengan status code yang tepat
    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 401  # 401 Unauthorized

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
                    error_msg = f"Item {i+1}: material dan pack_qty wajib diisi"
                    logger.error(f"❌ {error_msg}")
                    return {"success": False, "error": error_msg}, 400

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
                error_msg = f"Item {i+1}: {str(e)}"
                logger.error(f"❌ {error_msg}")
                return {"success": False, "error": error_msg}, 400

        params = {
            "I_HU_EXID": main_data['hu_exid'],
            "I_PACK_MAT": main_data['pack_mat'],
            "I_PLANT": main_data['plant'],
            "I_STGE_LOC": main_data['stge_loc'],
            "T_ITEMS": items
        }

        logger.info(f"📤 Memanggil RFC ZRFC_CREATE_HU_EXT dengan HU: {main_data['hu_exid']}")
        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        success, message = validate_sap_response(result)

        if success:
            # ✅ PERBAIKAN: Gunakan E_HUKEY dan HAPUS LEADING ZERO
            hu_number = result.get('E_HUKEY')
            hu_number_clean = hu_number.lstrip('0') or '0'
            logger.info(f"✅ HU Multi berhasil dibuat - HU Key: {hu_number} -> {hu_number_clean}")
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number_clean  # ✅ Tanpa leading zero
            }, 200
        else:
            return {"success": False, "error": message}, 400

    except Exception as e:
        error_msg = f"Error buat HU multi: {str(e)}"
        logger.error(f"❌ {error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

def create_multiple_hus(data):
    """Skenario 3: Multiple HU (Setiap HU 1 Material)"""
    logger.info("🟢 MEMULAI CREATE MULTIPLE HU")
    logger.info(f"📦 Data yang diterima: {list(data.keys())}")
    logger.info(f"📦 Jumlah HUs: {len(data.get('hus', []))}")

    if 'hus' not in data or not isinstance(data['hus'], list):
        error_msg = "Data 'hus' harus berupa array"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    if len(data['hus']) == 0:
        error_msg = "Data 'hus' tidak boleh kosong"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    # ✅ VALIDASI KETAT CREDENTIALS
    if 'sap_user' not in data or 'sap_password' not in data:
        error_msg = "SAP credentials (sap_user, sap_password) wajib diisi"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    # ✅ TOLAK CREDENTIALS DEFAULT
    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"🔐 Menggunakan SAP User dari request: {sap_user}")

    # ✅ PERBAIKAN: Tangani error koneksi SAP dengan status code yang tepat
    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"❌ {error_msg}")
        return {"success": False, "error": error_msg}, 401  # 401 Unauthorized

    try:
        results = []
        success_count = 0

        for i, hu_data in enumerate(data['hus']):
            try:
                logger.info(f"🔄 Memproses HU {i+1}/{len(data['hus'])}: {hu_data.get('hu_exid', 'UNKNOWN')}")

                required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
                for field in required_fields:
                    if field not in hu_data:
                        error_msg = f"Field {field} tidak ditemukan di HU {i+1}"
                        logger.error(f"❌ {error_msg}")
                        results.append({
                            "hu_exid": hu_data.get('hu_exid', f"HU_{i+1}"),
                            "success": False,
                            "error": error_msg
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

                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                success, message = validate_sap_response(result)

                if success:
                    # ✅ PERBAIKAN: Gunakan E_HUKEY dan HAPUS LEADING ZERO
                    hu_number = result.get('E_HUKEY')
                    hu_number_clean = hu_number.lstrip('0') or '0'
                    logger.info(f"✅ HU {i+1} berhasil - HU Key: {hu_number} -> {hu_number_clean}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": True,
                        "message": message,
                        "data": result,
                        "created_hu": hu_number_clean  # ✅ Tanpa leading zero
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
                logger.error(f"❌ HU {i+1} error: {error_msg}")
                results.append({
                    "hu_exid": hu_data.get('hu_exid', f'HU_{i+1}'),
                    "success": False,
                    "error": error_msg
                })
            except Exception as e:
                error_msg = f"Error processing HU: {str(e)}"
                logger.error(f"❌ HU {i+1} error: {error_msg}")
                results.append({
                    "hu_exid": hu_data.get('hu_exid', f'HU_{i+1}'),
                    "success": False,
                    "error": error_msg
                })

        logger.info(f"📊 Total HU: {len(results)}, Berhasil: {success_count}, Gagal: {len(results)-success_count}")

        # ✅ PERBAIKAN: Return status code berdasarkan hasil
        if success_count == 0:
            # Semua gagal
            return {
                "success": False,
                "message": f"Semua {len(results)} HU gagal dibuat",
                "data": results,
                "summary": {
                    "total": len(results),
                    "success": success_count,
                    "failed": len(results) - success_count
                }
            }, 400
        elif success_count == len(results):
            # Semua berhasil
            return {
                "success": True,
                "message": f"Semua {len(results)} HU berhasil dibuat",
                "data": results,
                "summary": {
                    "total": len(results),
                    "success": success_count,
                    "failed": len(results) - success_count
                }
            }, 200
        else:
            # Sebagian berhasil, sebagian gagal
            return {
                "success": True,  # Masih dianggap success karena ada yang berhasil
                "message": f"Processed {len(results)} HUs, {success_count} successful, {len(results)-success_count} failed",
                "data": results,
                "summary": {
                    "total": len(results),
                    "success": success_count,
                    "failed": len(results) - success_count
                }
            }, 207  # 207 Multi-Status

    except Exception as e:
        error_msg = f"Error buat multiple HU: {str(e)}"
        logger.error(f"❌ {error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

# ==================== ROUTES API ====================

@app.route('/hu/create-single', methods=['POST'])
def api_create_single():
    """Buat HU Skenario 1"""
    data = request.get_json()
    if not data:
        logger.error("❌ Data kosong diterima di /hu/create-single")
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("📍 /hu/create-single dipanggil")
    result, status_code = create_single_hu(data)  # ✅ PERBAIKAN: Terima status code
    return jsonify(result), status_code  # ✅ PERBAIKAN: Return status code yang tepat

@app.route('/hu/create-single-multi', methods=['POST'])
def api_create_single_multi():
    """Buat HU Skenario 2"""
    data = request.get_json()
    if not data:
        logger.error("❌ Data kosong diterima di /hu/create-single-multi")
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("📍 /hu/create-single-multi dipanggil")
    result, status_code = create_single_multi_hu(data)  # ✅ PERBAIKAN: Terima status code
    return jsonify(result), status_code  # ✅ PERBAIKAN: Return status code yang tepat

@app.route('/hu/create-multiple', methods=['POST'])
def api_create_multiple():
    """Buat HU Skenario 3"""
    data = request.get_json()
    if not data:
        logger.error("❌ Data kosong diterima di /hu/create-multiple")
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("📍 /hu/create-multiple dipanggil")

    if 'hus' not in data or not isinstance(data['hus'], list):
        return jsonify({"success": False, "error": "Data 'hus' harus berupa array"}), 400

    if len(data['hus']) == 0:
        return jsonify({"success": False, "error": "Data 'hus' tidak boleh kosong"}), 400

    if 'sap_user' not in data or 'sap_password' not in data:
        return jsonify({"success": False, "error": "SAP credentials (sap_user, sap_password) wajib diisi"}), 400

    result, status_code = create_multiple_hus(data)  # ✅ PERBAIKAN: Terima status code
    return jsonify(result), status_code  # ✅ PERBAIKAN: Return status code yang tepat

# ✅ TAMBAHKAN ENDPOINT UNTUK STOCK SYNC
@app.route('/stock/sync', methods=['POST'])
def api_stock_sync():
    """Manual trigger sync stock data"""
    logger.info("📍 /stock/sync dipanggil")

    try:
        data = request.get_json() or {}
        plant = data.get('plant', '3000')
        storage_location = data.get('storage_location', '3D10')

        logger.info(f"🔄 Memulai sync stock data: Plant {plant}, Storage Location {storage_location}")

        success = sync_stock_data(plant, storage_location)

        if success:
            logger.info("✅ Sync stock data berhasil")
            return jsonify({
                "success": True,
                "message": "Sync stock data completed successfully"
            }), 200
        else:
            logger.error("❌ Sync stock data gagal")
            return jsonify({
                "success": False,
                "error": "Sync stock data failed"
            }), 500

    except Exception as e:
        error_msg = f"Error dalam sync stock data: {str(e)}"
        logger.error(f"❌ {error_msg}")
        return jsonify({
            "success": False,
            "error": error_msg
        }), 500

@app.route('/sync/status', methods=['GET'])
def api_sync_status():
    """Get status sync terakhir"""
    return jsonify({
        "success": True,
        "data": last_sync_status
    }), 200

@app.route('/sync/now', methods=['POST'])
def api_sync_now():
    """Manual trigger sync"""
    try:
        success = sync_stock_data('3000', '3D10')
        if success:
            return jsonify({"success": True}), 200
        else:
            return jsonify({"success": False, "error": "Sync failed"}), 500
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500

@app.route('/health', methods=['GET'])
def api_health():
    """Health check API"""
    db_ok = connect_mysql() is not None
    sap_ok = connect_sap() is not None

    return jsonify({
        "success": True,
        "data": {
            "database": "connected" if db_ok else "disconnected",
            "sap": "connected" if sap_ok else "disconnected",
            "timestamp": datetime.now().isoformat()
        }
    }), 200

if __name__ == '__main__':
    logger.info("🚀 Starting SAP HU Automation API")

    ensure_magry_column_exists()

    if connect_mysql():
        logger.info("✅ Database siap")
    else:
        logger.error("❌ Database error")

    logger.info("🔧 Testing koneksi SAP...")
    sap_conn = connect_sap()
    if sap_conn:
        logger.info("✅ SAP siap")
        sap_conn.close()
    else:
        logger.warning("⚠️ SAP tidak tersedia")

    start_scheduler()

    logger.info("🌐 Starting Flask server di port 5000...")
    try:
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.error(f"❌ Gagal start Flask server: {e}")
    finally:
        stop_scheduler()
