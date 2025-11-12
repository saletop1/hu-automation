from flask import Flask, request, jsonify
from flask_cors import CORS
from pyrfc import Connection
import os
import logging
import pymysql
import pymysql.cursors
from apscheduler.schedulers.background import BackgroundScheduler
from datetime import datetime
import atexit
import time
from functools import wraps

app = Flask(__name__)

# Configure CORS properly
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000"], # Sesuaikan dengan URL Laravel Anda
        "methods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
        "allow_headers": ["Content-Type", "Authorization", "X-Requested-With"]
    }
})

@app.after_request
def after_request(response):
    response.headers.add('Access-Control-Allow-Origin', 'http://localhost:8000') # Sesuaikan
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
    response.headers.add('Access-Control-Allow-Credentials', 'true')
    return response

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# MySQL Configuration
MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'sap_hu_automation'),
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

# ===== FUNGSI KONEKSI DAN RETRY =====
def mysql_retry(max_retries=3, delay=2):
    """Decorator untuk retry MySQL connection"""
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            for attempt in range(max_retries):
                try:
                    return func(*args, **kwargs)
                except pymysql.Error as e:
                    logger.warning(f"MySQL connection failed (attempt {attempt + 1}/{max_retries}): {e}")
                    if attempt < max_retries - 1:
                        time.sleep(delay)
                    else:
                        logger.error(f"All MySQL connection attempts failed")
                        raise
        return wrapper
    return decorator

@mysql_retry(max_retries=3, delay=2)
def get_mysql_connection():
    """Membuat koneksi ke MySQL dengan retry mechanism"""
    try:
        connection = pymysql.connect(**MYSQL_CONFIG)
        logger.info("MySQL connection established successfully")
        return connection
    except pymysql.Error as e:
        logger.error(f"MySQL connection error: {e}")
        return None

def init_database():
    """Initialize database table jika belum ada"""
    connection = get_mysql_connection()
    if not connection:
        logger.error("Cannot initialize database - no connection")
        return False

    try:
        with connection.cursor() as cursor:
            # Create table jika belum ada
            create_table_sql = """
            CREATE TABLE IF NOT EXISTS stock_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                material VARCHAR(50) NOT NULL,
                material_description VARCHAR(255),
                plant VARCHAR(10) NOT NULL,
                storage_location VARCHAR(10) NOT NULL,
                batch VARCHAR(50),
                stock_quantity DECIMAL(15,3) DEFAULT 0,
                base_unit VARCHAR(10),
                sales_document VARCHAR(50),
                item_number VARCHAR(50),
                vendor_name VARCHAR(255),
                last_updated TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_stock (material, plant, storage_location, batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            """
            cursor.execute(create_table_sql)
            connection.commit()
            logger.info("Stock table initialized successfully")
            return True
    except Exception as e:
        logger.error(f"Database initialization error: {e}")
        return False
    finally:
        if connection:
            connection.close()

# ===== FUNGSI SAP CONNECTION =====
def format_material(material_code):
    """Menerapkan logic SAP 'ALPHA Conversion'."""
    if material_code and material_code.isdigit():
        return material_code.zfill(18)
    else:
        return material_code

def connect_sap(user, passwd):
    """Membuka koneksi ke SAP."""
    try:
        ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sysnr = os.getenv("SAP_SYSNR", "01")
        client = os.getenv("SAP_CLIENT", "300")

        logger.info(f"Connecting to SAP: {ashost}, sysnr: {sysnr}, client: {client}")

        conn = Connection(
            user=user,
            passwd=passwd,
            ashost=ashost,
            sysnr=sysnr,
            client=client,
            lang="EN"
        )
        conn.ping()
        logger.info("Koneksi SAP berhasil dibuat.")
        return conn
    except Exception as e:
        logger.error(f"Gagal saat membuka koneksi ke SAP: {e}")
        return None

# ===== FUNGSI STOCK DATA =====
def fetch_stock_from_sap(plant=None, storage_location=None):
    """Mengambil data stock dari SAP RFC Z_FM_YMMR006NX"""
    sap_user = os.getenv("SAP_USER")
    sap_password = os.getenv("SAP_PASSWORD")

    if not sap_user or not sap_password:
        logger.error("SAP credentials missing for stock sync")
        return False

    if plant is None:
        plant = os.getenv('SAP_DEFAULT_PLANT', '3000')
    if storage_location is None:
        storage_location = os.getenv('SAP_DEFAULT_STORAGE_LOCATION', '3D10')

    conn = connect_sap(sap_user, sap_password)
    if not conn:
        logger.error("Cannot connect to SAP for stock sync")
        return False

    try:
        logger.info(f"Fetching stock data from SAP RFC Z_FM_YMMR006NX with plant={plant}, storage_location={storage_location}")

        result = conn.call('Z_FM_YMMR006NX',
                          P_WERKS=plant,
                          P_MTART='FERT',
                          P_LGORT=storage_location)

        stock_data = result.get('T_DATA', [])
        record_count = len(stock_data)
        logger.info(f"Retrieved {record_count} stock records from SAP")

        if record_count > 0:
            mysql_conn = get_mysql_connection()
            if not mysql_conn:
                logger.error("Cannot connect to MySQL")
                return False

            try:
                with mysql_conn.cursor() as cursor:
                    # PERBAIKAN: Gunakan .strip() pada plant/sloc untuk delete
                    delete_sql = "DELETE FROM stock_data WHERE plant = %s AND storage_location = %s"
                    cursor.execute(delete_sql, (plant.strip(), storage_location.strip()))
                    logger.info(f"Deleted old stock data for plant {plant}, storage location {storage_location}")

                    for item in stock_data:
                        vendor_name = item.get('NAME1', '')
                        if vendor_name:
                            vendor_name = ' '.join(vendor_name.split()[:2])

                        # Gunakan ON DUPLICATE KEY UPDATE untuk mengatasi error 1062
                        sql = """
                        INSERT INTO stock_data
                        (material, material_description, plant, storage_location, batch, stock_quantity,
                         base_unit, sales_document, item_number, vendor_name, last_updated, created_at, updated_at)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                        material_description = VALUES(material_description),
                        stock_quantity = VALUES(stock_quantity),
                        base_unit = VALUES(base_unit),
                        sales_document = VALUES(sales_document),
                        item_number = VALUES(item_number),
                        vendor_name = VALUES(vendor_name),
                        last_updated = VALUES(last_updated),
                        updated_at = NOW()
                        """

                        # ===== PERBAIKAN DI SINI: Gunakan .strip() untuk membersihkan data =====
                        cursor.execute(sql, (
                            item.get('MATNR', '').strip(),
                            item.get('MAKTX', '').strip(),
                            item.get('WERKS', '').strip(),  # Menggunakan WERKS sesuai konfirmasi Anda
                            item.get('LGORT', '').strip(),
                            item.get('CHARG', '').strip(),
                            item.get('CLABS', 0),
                            item.get('MEINS', '').strip(),
                            item.get('VBELN', '').strip(),
                            item.get('POSNR', '').strip(),
                            vendor_name.strip(),
                            datetime.now()
                        ))
                        # ==========================================================

                    mysql_conn.commit()
                    logger.info(f"Stock data successfully saved to MySQL for plant {plant}, storage location {storage_location}")

            except Exception as e:
                logger.error(f"Error saving stock data to MySQL: {e}")
                mysql_conn.rollback()
                return False
            finally:
                if mysql_conn:
                    mysql_conn.close()
        else:
            logger.warning(f"SAP returned 0 records for {plant}/{storage_location}. Skipping database update.")

        return True

    except Exception as e:
        logger.error(f"Error fetching stock from SAP: {e}")
        return False
    finally:
        if conn:
            conn.close()

# ===== SCHEDULER UNTUK AUTO-SYNC =====
scheduler = BackgroundScheduler()
scheduler.add_job(func=fetch_stock_from_sap, trigger="interval", minutes=30)
scheduler.start()
atexit.register(lambda: scheduler.shutdown())

# ===== ROUTES UNTUK STOCK DATA =====
@app.route('/stock/sync', methods=['POST', 'OPTIONS'])
def sync_stock():
    """Manual sync stock data dari SAP"""
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json() or {}
        plant = data.get('plant', os.getenv('SAP_DEFAULT_PLANT', '3000'))
        storage_location = data.get('storage_location', os.getenv('SAP_DEFAULT_STORAGE_LOCATION', '3D10'))
        logger.info(f"Manual stock sync requested for plant: {plant}, storage_location: {storage_location}")

        success = fetch_stock_from_sap(plant, storage_location)

        if success:
            return jsonify({"success": True, "message": f"Stock data synced successfully for plant {plant}, storage location {storage_location}"})
        else:
            return jsonify({"success": False, "error": "Failed to sync stock data from SAP. Check logs."}), 500

    except Exception as e:
        logger.error(f"Error in manual stock sync: {e}")
        return jsonify({"success": False, "error": f"Sync failed: {str(e)}"}), 500

@app.route('/stock/plants', methods=['GET'])
def get_plants():
    """Get distinct plants from stock data"""
    try:
        connection = get_mysql_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500
        with connection.cursor() as cursor:
            # Data sudah bersih, tidak perlu TRIM
            cursor.execute("SELECT DISTINCT plant FROM stock_data ORDER BY plant")
            plants = [row['plant'] for row in cursor.fetchall()]
        connection.close()
        return jsonify({"success": True, "data": plants})
    except Exception as e:
        logger.error(f"Error getting plants: {e}")
        return jsonify({"success": False, "error": f"Failed to get plants: {str(e)}"}), 500

@app.route('/stock/storage-locations', methods=['GET'])
def get_storage_locations():
    """Get distinct storage locations from stock data"""
    try:
        plant = request.args.get('plant', '')
        connection = get_mysql_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500
        with connection.cursor() as cursor:
            if plant:
                # Data sudah bersih, tidak perlu TRIM
                cursor.execute("SELECT DISTINCT storage_location FROM stock_data WHERE plant = %s ORDER BY storage_location", (plant,))
            else:
                cursor.execute("SELECT DISTINCT storage_location FROM stock_data ORDER BY storage_location")
            storage_locations = [row['storage_location'] for row in cursor.fetchall()]
        connection.close()
        return jsonify({"success": True, "data": storage_locations})
    except Exception as e:
        logger.error(f"Error getting storage locations: {e}")
        return jsonify({"success": False, "error": f"Failed to get storage locations: {str(e)}"}), 500

@app.route('/stock', methods=['GET'])
def get_stock():
    """Get stock data dari MySQL dengan filter"""
    try:
        connection = get_mysql_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500
        with connection.cursor() as cursor:
            page = request.args.get('page', 1, type=int)
            per_page = request.args.get('per_page', 1000, type=int) # Naikkan default
            material_filter = request.args.get('material', '')
            plant_filter = request.args.get('plant', '')
            storage_location_filter = request.args.get('storage_location', '')
            offset = (page - 1) * per_page

            # Data sudah bersih, tidak perlu TRIM
            query = "SELECT * FROM stock_data WHERE 1=1"
            count_query = "SELECT COUNT(*) as total FROM stock_data WHERE 1=1"
            params = []
            count_params = []

            if material_filter:
                query += " AND material LIKE %s"
                count_query += " AND material LIKE %s"
                params.append(f"%{material_filter}%")
                count_params.append(f"%{material_filter}%")
            if plant_filter:
                query += " AND plant = %s"
                count_query += " AND plant = %s"
                params.append(plant_filter)
                count_params.append(plant_filter)
            if storage_location_filter:
                query += " AND storage_location = %s"
                count_query += " AND storage_location = %s"
                params.append(storage_location_filter)
                count_params.append(storage_location_filter)

            query += " ORDER BY material, plant, storage_location, batch LIMIT %s OFFSET %s"
            params.extend([per_page, offset])

            cursor.execute(query, params)
            stock_data = cursor.fetchall()

            cursor.execute(count_query, count_params)
            total_count = cursor.fetchone()['total']
        connection.close()

        return jsonify({
            "success": True,
            "data": stock_data,
            "pagination": {
                "current_page": page,
                "per_page": per_page,
                "total": total_count,
                "total_pages": (total_count + per_page - 1) // per_page
            }
        })
    except Exception as e:
        logger.error(f"Error getting stock data: {e}")
        return jsonify({"success": False, "error": f"Failed to get stock data: {str(e)}"}), 500

# ==========================================================
# ===== ROUTES HU CREATION (DENGAN LOGIKA SAP ASLI) =====
# ==========================================================
@app.route('/hu/create-single', methods=['POST', 'OPTIONS'])
def create_single_hu():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        logger.info(f"Creating single HU with data: { {k: v for k, v in data.items() if k != 'sap_password'} }")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")
        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        # Terapkan format material
        formatted_material = format_material(data.get('material'))
        formatted_pack_mat = format_material(data.get('pack_mat'))

        params = {
            "I_HU_EXID": data.get('hu_exid'),
            "I_PACK_MAT": formatted_pack_mat,
            "I_PLANT": data.get('plant'),
            "I_STGE_LOC": data.get('stge_loc'),
            "T_ITEMS": [{
                "MATERIAL": formatted_material,
                "PLANT": data.get('plant'),
                "STGE_LOC": data.get('stge_loc'),
                "PACK_QTY": str(data.get('pack_qty', 0)),
                # PERBAIKAN: Ubah None/Null menjadi string kosong ""
                "BASE_UNIT_QTY": data.get('base_unit_qty') or '',
                "HU_ITEM_TYPE": "1",
                "BATCH": data.get('batch', ''),
                "SPEC_STOCK": "E", # Asumsi 'E'
                "SP_STCK_NO": data.get('sp_stck_no', ''),
                "GR_DATE": data.get('gr_date', '')
            }]
        }

        logger.info(f"Calling ZRFC_CREATE_HU_EXT with params: {params}")
        result = conn.call('ZRFC_CREATE_HU_EXT', **params)
        conn.close()

        # Cek jika SAP mengembalikan error
        if 'E_RETURN' in result and result['E_RETURN'] and result['E_RETURN']['TYPE'] in ['E', 'A']:
            error_message = result['E_RETURN']['MESSAGE']
            logger.error(f"SAP Error creating single HU: {error_message} | Data: {result}")
            return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

        logger.info(f"HU created successfully: {result}")
        return jsonify({
            "success": True,
            "data": result,
            "message": "Single HU created successfully"
        })

    except Exception as e:
        error_message = str(e)
        logger.error(f"Error creating single HU: {error_message}")
        return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

@app.route('/hu/create-single-multi', methods=['POST', 'OPTIONS'])
def create_single_hu_multi():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        logger.info(f"Creating single HU multi with data: { {k: v for k, v in data.items() if k != 'sap_password'} }")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")
        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        items = []
        for item in data.get('items', []):
            formatted_material = format_material(item.get('material'))
            items.append({
                "MATERIAL": formatted_material,
                "PLANT": data.get('plant'),
                "STGE_LOC": data.get('stge_loc'),
                "PACK_QTY": str(item.get('pack_qty', 0)),
                # PERBAIKAN: Ubah None/Null menjadi string kosong ""
                "BASE_UNIT_QTY": item.get('base_unit_qty') or '',
                "HU_ITEM_TYPE": "1",
                "BATCH": item.get('batch', ''),
                "SPEC_STOCK": "E",
                "SP_STCK_NO": item.get('sp_stck_no', ''),
                "GR_DATE": item.get('gr_date', '')
            })

        formatted_pack_mat = format_material(data.get('pack_mat'))

        params = {
            "I_HU_EXID": data.get('hu_exid'),
            "I_PACK_MAT": formatted_pack_mat,
            "I_PLANT": data.get('plant'),
            "I_STGE_LOC": data.get('stge_loc'),
            "T_ITEMS": items
        }

        logger.info(f"Calling ZRFC_CREATE_HU_EXT with params: {params}")
        result = conn.call('ZRFC_CREATE_HU_EXT', **params)
        conn.close()

        # Cek jika SAP mengembalikan error
        if 'E_RETURN' in result and result['E_RETURN'] and result['E_RETURN']['TYPE'] in ['E', 'A']:
            error_message = result['E_RETURN']['MESSAGE']
            logger.error(f"SAP Error creating single HU multi: {error_message} | Data: {result}")
            return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

        logger.info(f"Single HU multi created successfully: {result}")
        return jsonify({
            "success": True,
            "data": result,
            "message": "Single HU with multiple materials created successfully"
        })

    except Exception as e:
        error_message = str(e)
        logger.error(f"Error creating single HU multi: {error_message}")
        return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

@app.route('/hu/create-multiple', methods=['POST', 'OPTIONS'])
def create_multiple_hus():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        logger.info(f"Creating multiple HUs: { {k: v for k, v in data.items() if k != 'sap_password'} }")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")
        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        results = []
        errors = []

        for hu_data in data.get('hus', []):
            try:
                formatted_material = format_material(hu_data.get('material'))
                formatted_pack_mat = format_material(hu_data.get('pack_mat'))

                params = {
                    "I_HU_EXID": hu_data.get('hu_exid'),
                    "I_PACK_MAT": formatted_pack_mat,
                    "I_PLANT": hu_data.get('plant'),
                    "I_STGE_LOC": hu_data.get('stge_loc'),
                    "T_ITEMS": [{
                        "MATERIAL": formatted_material,
                        "PLANT": hu_data.get('plant'),
                        "STGE_LOC": hu_data.get('stge_loc'),
                        "PACK_QTY": str(hu_data.get('pack_qty', 0)),
                        # PERBAIKAN: Ubah None/Null menjadi string kosong ""
                        "BASE_UNIT_QTY": hu_data.get('base_unit_qty') or '',
                        "HU_ITEM_TYPE": "1",
                        "BATCH": hu_data.get('batch', ''),
                        "SPEC_STOCK": "E",
                        "SP_STCK_NO": hu_data.get('sp_stck_no', ''),
                        "GR_DATE": hu_data.get('gr_date', '')
                    }]
                }

                logger.info(f"Processing HU: {hu_data.get('hu_exid')}")
                result = conn.call('ZRFC_CREATE_HU_EXT', **params)

                # Cek jika SAP mengembalikan error
                if 'E_RETURN' in result and result['E_RETURN'] and result['E_RETURN']['TYPE'] in ['E', 'A']:
                    error_message = result['E_RETURN']['MESSAGE']
                    logger.warning(f"Failed to create HU {hu_data.get('hu_exid')}: {error_message}")
                    errors.append({"hu_exid": hu_data.get('hu_exid'), "error": error_message})
                else:
                    results.append({"hu_exid": hu_data.get('hu_exid'), "result": result})

            except Exception as e:
                error_message = str(e)
                logger.warning(f"Failed to create HU {hu_data.get('hu_exid')}: {error_message}")
                errors.append({"hu_exid": hu_data.get('hu_exid'), "error": error_message})

        conn.close()

        if errors:
            return jsonify({
                "success": False,
                "error": "Some HUs failed to create. See details.",
                "successful_hus": results,
                "failed_hus": errors
            }), 500

        logger.info(f"All {len(results)} HUs created successfully")
        return jsonify({
            "success": True,
            "data": results,
            "message": f"All {len(results)} HUs created successfully"
        })

    except Exception as e:
        error_message = str(e)
        logger.error(f"Error creating multiple HUs (connection/setup failed): {error_message}")
        return jsonify({"success": False, "error": f"Failed to create HUs: {error_message}"}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({"status": "healthy", "timestamp": datetime.now().isoformat()})

@app.route('/', methods=['GET'])
def home():
    """Home endpoint"""
    return jsonify({
        "message": "SAP HU Automation Python API",
        "endpoints": {
            "stock": "/stock",
            "stock_sync": "/stock/sync",
            "hu_create_single": "/hu/create-single",
            "health": "/health"
        }
    })

if __name__ == '__main__':
    logger.info("Initializing database...")
    if init_database():
        logger.info("Starting initial stock data sync...")
        fetch_stock_from_sap()

    app.run(host='0.0.0.0', port=5000, debug=True)
