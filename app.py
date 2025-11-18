from flask import Flask, request, jsonify
from flask_cors import CORS
from pyrfc import Connection
import os
import logging
import pymysql
from datetime import datetime, timedelta
from decimal import Decimal
import sys
import io
from apscheduler.schedulers.background import BackgroundScheduler
import atexit
import time

# Fix encoding untuk Windows
if sys.platform.startswith('win'):
    if sys.stdout.encoding != 'utf-8':
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    if sys.stderr.encoding != 'utf-8':
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

app = Flask(__name__)
CORS(app)

# Setup logging TANPA EMOJI
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

# ===== SET ENVIRONMENT VARIABLES DI LEVEL MODULE =====
# Ini akan memastikan variables tersedia untuk semua thread
os.environ['SAP_USER'] = os.getenv('SAP_USER', 'auto_email')
os.environ['SAP_PASSWORD'] = os.getenv('SAP_PASSWORD', '11223344')
os.environ['SAP_ASHOST'] = os.getenv('SAP_ASHOST', '192.168.254.154')
os.environ['SAP_SYSNR'] = os.getenv('SAP_SYSNR', '01')
os.environ['SAP_CLIENT'] = os.getenv('SAP_CLIENT', '300')

# ===== SCHEDULER UNTUK AUTO SYNC =====
scheduler = BackgroundScheduler()

# Variabel global untuk melacak status sync terakhir
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
    """Job untuk auto sync setiap 10 menit"""
    if last_sync_status['is_running']:
        logger.info("Auto sync ditunda karena proses sync sedang berjalan")
        return

    last_sync_status['is_running'] = True
    logger.info("Auto sync dimulai...")

    try:
        # Default plant dan storage location untuk auto sync
        plant = '3000'
        storage_location = '3D10'

        success = sync_stock_data(plant, storage_location)

        if success:
            logger.info("Auto sync selesai")
            update_sync_status(success=True)
        else:
            logger.error("Auto sync gagal")
            update_sync_status(success=False, error="Auto sync gagal")

    except Exception as e:
        error_msg = f"Error dalam auto sync: {e}"
        logger.error(error_msg)
        update_sync_status(success=False, error=error_msg)

def start_scheduler():
    """Mulai scheduler untuk auto sync"""
    try:
        # Jadwalkan auto sync setiap 10 menit
        scheduler.add_job(
            func=auto_sync_job,
            trigger='interval',
            minutes=10,
            id='auto_sync_job',
            replace_existing=True
        )

        scheduler.start()
        logger.info("Scheduler started - Auto sync setiap 10 menit")

    except Exception as e:
        logger.error(f"Gagal start scheduler: {e}")

def stop_scheduler():
    """Stop scheduler saat aplikasi berhenti"""
    if scheduler.running:
        scheduler.shutdown()
        logger.info("Scheduler dihentikan")

# Register shutdown handler
atexit.register(stop_scheduler)

# ===== FUNGSI DASAR =====
def connect_sap():
    """Buka koneksi ke SAP dengan error handling dan fallback"""
    try:
        # Gunakan nilai default jika environment variables tidak ada
        sap_user = os.getenv("SAP_USER", "auto_email")
        sap_password = os.getenv("SAP_PASSWORD", "11223344")
        sap_ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sap_sysnr = os.getenv("SAP_SYSNR", "01")
        sap_client = os.getenv("SAP_CLIENT", "300")

        logger.info(f"Mencoba koneksi SAP dengan user: {sap_user}")

        conn = Connection(
            user=sap_user,
            passwd=sap_password,
            ashost=sap_ashost,
            sysnr=sap_sysnr,
            client=sap_client,
            lang="EN"
        )

        # Test koneksi
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
        logger.info("Koneksi MySQL berhasil")
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

# ===== SYNC STOCK DATA =====
def sync_stock_data(plant='3000', storage_location='3D10'):
    """Sync data stock dari SAP ke database"""
    logger.info(f"Mulai sync stock - Plant: {plant}, Lokasi: {storage_location}")

    sap_conn = connect_sap()
    if not sap_conn:
        logger.error("Tidak dapat melanjutkan sync karena koneksi SAP gagal")
        return False

    try:
        # Ambil data dari SAP
        logger.info("Mengambil data dari SAP...")
        result = sap_conn.call('Z_FM_YMMR006NX',
                             P_WERKS=plant,
                             P_MTART='FERT',
                             P_LGORT=storage_location)

        stock_data = result.get('T_DATA', [])
        logger.info(f"Data diterima: {len(stock_data)} record")

        if not stock_data:
            logger.info("Tidak ada data dari SAP")
            return True

        mysql_conn = connect_mysql()
        if not mysql_conn:
            return False

        try:
            with mysql_conn.cursor() as cursor:
                inserted = 0
                updated = 0

                for item in stock_data:
                    material = clean_value(item.get('MATNR'))
                    desc = clean_value(item.get('MAKTX'))
                    qty = convert_qty(item.get('CLABS'))

                    if not material:
                        continue

                    sql = """
                    INSERT INTO stock_data
                    (material, material_description, plant, storage_location, batch, stock_quantity, base_unit, last_updated)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                    material_description = VALUES(material_description),
                    stock_quantity = VALUES(stock_quantity),
                    last_updated = VALUES(last_updated)
                    """

                    cursor.execute(sql, (
                        material, desc, plant, storage_location,
                        clean_value(item.get('CHARG')), float(qty),
                        clean_value(item.get('MEINS')), datetime.now()
                    ))

                    if cursor.rowcount == 1:
                        inserted += 1
                    else:
                        updated += 1

                mysql_conn.commit()
                logger.info(f"Sync selesai: {inserted} baru, {updated} update")
                return True

        except Exception as e:
            logger.error(f"Error database: {e}")
            mysql_conn.rollback()
            return False
        finally:
            mysql_conn.close()

    except Exception as e:
        logger.error(f"Error SAP: {e}")
        return False
    finally:
        sap_conn.close()

# ===== CREATE HU - 3 SKENARIO =====
def create_single_hu(data):
    """Skenario 1: 1 HU dengan 1 Material"""
    logger.info("Buat HU Skenario 1 - 1 HU 1 Material")

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        params = {
            "I_HU_EXID": data['hu_exid'],
            "I_PACK_MAT": data['pack_mat'],
            "I_PLANT": data['plant'],
            "I_STGE_LOC": data['stge_loc'],
            "T_ITEMS": [{
                "MATERIAL": data['material'],
                "PLANT": data['plant'],
                "STGE_LOC": data['stge_loc'],
                "PACK_QTY": str(data['pack_qty']),
                "HU_ITEM_TYPE": "1",
                "BATCH": data.get('batch', ''),
                "SPEC_STOCK": "E",
                "SP_STCK_NO": data.get('sp_stck_no', '')
            }]
        }

        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        if 'E_RETURN' in result and result['E_RETURN']:
            error_msg = result['E_RETURN']['MESSAGE']
            logger.error(f"SAP error: {error_msg}")
            return {"success": False, "error": error_msg}

        logger.info(f"HU {data['hu_exid']} berhasil dibuat")
        return {"success": True, "data": result}

    except Exception as e:
        logger.error(f"Error buat HU: {e}")
        return {"success": False, "error": str(e)}
    finally:
        sap_conn.close()

def create_single_multi_hu(data):
    """Skenario 2: 1 HU dengan Multiple Material"""
    logger.info("Buat HU Skenario 2 - 1 HU Multiple Material")

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        items = []
        for item in data['items']:
            items.append({
                "MATERIAL": item['material'],
                "PLANT": data['plant'],
                "STGE_LOC": data['stge_loc'],
                "PACK_QTY": str(item['pack_qty']),
                "HU_ITEM_TYPE": "1",
                "BATCH": item.get('batch', ''),
                "SPEC_STOCK": "E",
                "SP_STCK_NO": item.get('sp_stck_no', '')
            })

        params = {
            "I_HU_EXID": data['hu_exid'],
            "I_PACK_MAT": data['pack_mat'],
            "I_PLANT": data['plant'],
            "I_STGE_LOC": data['stge_loc'],
            "T_ITEMS": items
        }

        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        if 'E_RETURN' in result and result['E_RETURN']:
            error_msg = result['E_RETURN']['MESSAGE']
            logger.error(f"SAP error: {error_msg}")
            return {"success": False, "error": error_msg}

        logger.info(f"HU {data['hu_exid']} dengan {len(items)} material berhasil dibuat")
        return {"success": True, "data": result}

    except Exception as e:
        logger.error(f"Error buat HU multi: {e}")
        return {"success": False, "error": str(e)}
    finally:
        sap_conn.close()

def create_multiple_hus(data):
    """Skenario 3: Multiple HU (Setiap HU 1 Material)"""
    logger.info("Buat HU Skenario 3 - Multiple HU")

    sap_conn = connect_sap()
    if not sap_conn:
        return {"success": False, "error": "Gagal konek SAP"}

    try:
        results = []
        success_count = 0

        for hu_data in data['hus']:
            try:
                params = {
                    "I_HU_EXID": hu_data['hu_exid'],
                    "I_PACK_MAT": hu_data['pack_mat'],
                    "I_PLANT": hu_data['plant'],
                    "I_STGE_LOC": hu_data['stge_loc'],
                    "T_ITEMS": [{
                        "MATERIAL": hu_data['material'],
                        "PLANT": hu_data['plant'],
                        "STGE_LOC": hu_data['stge_loc'],
                        "PACK_QTY": str(hu_data['pack_qty']),
                        "HU_ITEM_TYPE": "1",
                        "BATCH": hu_data.get('batch', ''),
                        "SPEC_STOCK": "E",
                        "SP_STCK_NO": hu_data.get('sp_stck_no', '')
                    }]
                }

                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                if 'E_RETURN' in result and result['E_RETURN']:
                    error_msg = result['E_RETURN']['MESSAGE']
                    results.append({"hu_exid": hu_data['hu_exid'], "success": False, "error": error_msg})
                    logger.error(f"HU {hu_data['hu_exid']} gagal: {error_msg}")
                else:
                    results.append({"hu_exid": hu_data['hu_exid'], "success": True, "data": result})
                    success_count += 1
                    logger.info(f"HU {hu_data['hu_exid']} berhasil")

            except Exception as e:
                results.append({"hu_exid": hu_data['hu_exid'], "success": False, "error": str(e)})
                logger.error(f"HU {hu_data['hu_exid']} error: {e}")

        logger.info(f"Total HU: {len(results)}, Berhasil: {success_count}, Gagal: {len(results)-success_count}")
        return {"success": success_count > 0, "data": results}

    except Exception as e:
        logger.error(f"Error buat multiple HU: {e}")
        return {"success": False, "error": str(e)}
    finally:
        sap_conn.close()

# ===== ROUTES API =====
@app.route('/stock/sync', methods=['POST'])
def api_sync_stock():
    """Sync stock manual dari Laravel"""
    if last_sync_status['is_running']:
        return jsonify({"success": False, "error": "Sync sedang berjalan, coba lagi nanti"}), 429

    data = request.get_json() or {}
    plant = data.get('plant', '3000')
    storage_location = data.get('storage_location', '3D10')

    last_sync_status['is_running'] = True

    try:
        success = sync_stock_data(plant, storage_location)

        if success:
            update_sync_status(success=True)
            return jsonify({
                "success": True,
                "message": "Sync berhasil",
                "last_sync": datetime.now().isoformat()
            })
        else:
            update_sync_status(success=False, error="Sync manual gagal")
            return jsonify({"success": False, "error": "Sync gagal"}), 500
    except Exception as e:
        error_msg = f"Error dalam sync manual: {e}"
        update_sync_status(success=False, error=error_msg)
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
            "is_running": last_sync_status['is_running'],
            "next_auto_sync": scheduler.get_job('auto_sync_job').next_run_time.isoformat() if scheduler.get_job('auto_sync_job') and scheduler.get_job('auto_sync_job').next_run_time else None
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
                       batch, stock_quantity, base_unit, last_updated
                FROM stock_data
                ORDER BY material_description, material
            """)
            data = cursor.fetchall()

            # Convert datetime objects to string
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

@app.route('/scheduler/status', methods=['GET'])
def scheduler_status():
    """Cek status scheduler"""
    jobs = scheduler.get_jobs()
    job_info = []

    for job in jobs:
        job_info.append({
            'id': job.id,
            'next_run': str(job.next_run_time) if job.next_run_time else 'None',
            'trigger': str(job.trigger)
        })

    return jsonify({
        'scheduler_running': scheduler.running,
        'jobs': job_info
    })

# ===== JALANKAN APLIKASI =====
if __name__ == '__main__':
    logger.info("Starting SAP HU Automation API")

    # Test koneksi database
    if connect_mysql():
        logger.info("Database siap")
    else:
        logger.error("Database error - aplikasi tetap berjalan tapi sync akan gagal")

    # Test koneksi SAP (tapi jangan berhenti jika gagal)
    sap_conn = connect_sap()
    if sap_conn:
        logger.info("SAP siap")
        sap_conn.close()
    else:
        logger.warning("SAP tidak tersedia - fitur sync dan create HU akan gagal")

    # Start scheduler untuk auto sync
    start_scheduler()

    # Jalankan sync pertama saat start
    logger.info("Jalankan sync pertama...")
    try:
        last_sync_status['is_running'] = True
        sync_stock_data()
        update_sync_status(success=True)
    except Exception as e:
        error_msg = f"Sync pertama gagal: {e}"
        logger.error(error_msg)
        update_sync_status(success=False, error=error_msg)

    # API tetap berjalan meskipun SAP tidak tersedia
    logger.info("Starting Flask server di port 5000...")

    try:
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.error(f"Gagal start Flask server: {e}")
    finally:
        stop_scheduler()

