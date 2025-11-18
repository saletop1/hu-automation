from flask import Flask, request, jsonify
from flask_cors import CORS
from pyrfc import Connection
import os
import logging
from logging.handlers import RotatingFileHandler
import pymysql
import pymysql.cursors
from apscheduler.schedulers.background import BackgroundScheduler
from datetime import datetime
import atexit
import time
from functools import wraps
import re
from decimal import Decimal
import sys
import io

# Fix encoding untuk Windows - HARUS DI ATAS SEMUA
if sys.platform.startswith('win'):
    if sys.stdout.encoding != 'utf-8':
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    if sys.stderr.encoding != 'utf-8':
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

app = Flask(__name__)

# Configure CORS properly
CORS(app, resources={
    r"/*": {
        "origins": ["http://localhost:8000", "http://127.0.0.1:8000", "http://localhost:5000", "http://127.0.0.1:5000"],
        "methods": ["GET", "POST", "PUT", "DELETE", "OPTIONS"],
        "allow_headers": ["Content-Type", "Authorization", "X-Requested-With"]
    }
})

@app.after_request
def after_request(response):
    response.headers.add('Access-Control-Allow-Origin', '*')
    response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Requested-With')
    response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
    response.headers.add('Access-Control-Allow-Credentials', 'true')
    return response

# Setup logging TANPA EMOJI
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('sap_hu_automation.log', encoding='utf-8')
    ]
)
logger = logging.getLogger(__name__)

# MySQL Configuration
MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'sap_hu_automation'),
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor,
    'autocommit': False
}

# ===== FUNGSI KONEKSI DAN RETRY =====
def mysql_retry(max_retries=3, delay=2):
    """Decorator untuk retry MySQL connection"""
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            last_exception = None
            for attempt in range(max_retries):
                try:
                    return func(*args, **kwargs)
                except pymysql.Error as e:
                    last_exception = e
                    logger.warning(f"MySQL connection failed (attempt {attempt + 1}/{max_retries}): {e}")
                    if attempt < max_retries - 1:
                        time.sleep(delay * (attempt + 1))
                    else:
                        logger.error(f"All MySQL connection attempts failed")
                        raise last_exception
            return None
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
            # Create stock_data table
            create_stock_table_sql = """
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
                hu_created BOOLEAN DEFAULT FALSE,
                hu_created_at TIMESTAMP NULL,
                hu_number VARCHAR(50),
                UNIQUE KEY unique_stock (material, plant, storage_location, batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            """
            cursor.execute(create_stock_table_sql)

            # Create hu_histories table
            create_hu_history_sql = """
            CREATE TABLE IF NOT EXISTS hu_histories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stock_id INT,
                hu_number VARCHAR(50) NOT NULL,
                material VARCHAR(50) NOT NULL,
                material_description VARCHAR(255),
                batch VARCHAR(50),
                quantity DECIMAL(15,3) DEFAULT 0,
                unit VARCHAR(10) DEFAULT 'PC',
                sales_document VARCHAR(50),
                plant VARCHAR(10) NOT NULL,
                storage_location VARCHAR(10) NOT NULL,
                scenario_type VARCHAR(20),
                created_by VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (stock_id) REFERENCES stock_data(id) ON DELETE SET NULL,
                INDEX idx_hu_number (hu_number),
                INDEX idx_material (material),
                INDEX idx_stock_id (stock_id),
                INDEX idx_plant_storage (plant, storage_location)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            """
            cursor.execute(create_hu_history_sql)

            connection.commit()
            logger.info("Database tables initialized successfully")
            return True
    except Exception as e:
        logger.error(f"Database initialization error: {e}")
        connection.rollback()
        return False
    finally:
        if connection:
            connection.close()

# ===== FUNGSI UTILITAS =====
def clean_sap_data(value):
    """Membersihkan data dari SAP dari spasi dan karakter non-printable"""
    if value is None:
        return ''
    if isinstance(value, str):
        cleaned = value.strip()
        cleaned = re.sub(r'\s+', ' ', cleaned)
        return cleaned
    return str(value)

def format_material(material_code):
    """Menerapkan logic SAP 'ALPHA Conversion'."""
    if material_code and material_code.isdigit():
        return material_code.zfill(18)
    else:
        return material_code

def safe_convert_to_decimal(value, default=0):
    """Safely convert value to Decimal dengan presisi 3 digit"""
    try:
        if value is None:
            return Decimal(default)
        return Decimal(str(value)).quantize(Decimal('0.001'))
    except (ValueError, TypeError):
        logger.warning(f"Cannot convert value to Decimal: {value}, using default: {default}")
        return Decimal(default)

def normalize_material_key(material, batch, plant, storage_location):
    """Normalize material key untuk konsistensi pencarian"""
    material_clean = clean_sap_data(material)
    batch_clean = clean_sap_data(batch) or ''
    plant_clean = clean_sap_data(plant)
    storage_clean = clean_sap_data(storage_location)

    return f"{material_clean}_{batch_clean}_{plant_clean}_{storage_clean}"

# ===== FUNGSI SAP CONNECTION =====
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

# ===== FUNGSI STOCK DATA - LOGIKA YANG DIPERBAIKI =====
def fetch_stock_from_sap(plant=None, storage_location=None):
    """Mengambil data stock dari SAP dan hitung stock available berdasarkan HU history"""
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
                    cleaned_plant = clean_sap_data(plant)
                    cleaned_storage_location = clean_sap_data(storage_location)

                    # STEP 1: Ambil TOTAL quantity dari HU histories untuk plant/storage_location ini
                    cursor.execute("""
                        SELECT material, batch, SUM(quantity) as total_hu_quantity
                        FROM hu_histories
                        WHERE plant = %s AND storage_location = %s
                        GROUP BY material, batch
                    """, (cleaned_plant, cleaned_storage_location))

                    hu_totals = {}
                    for row in cursor.fetchall():
                        key = normalize_material_key(
                            row['material'],
                            row['batch'],
                            cleaned_plant,
                            cleaned_storage_location
                        )
                        hu_totals[key] = safe_convert_to_decimal(row['total_hu_quantity'])

                    logger.info(f"Found {len(hu_totals)} material-batch combinations with HU history")

                    # STEP 2: Ambil existing stock_data untuk mapping ID
                    cursor.execute("""
                        SELECT id, material, batch, plant, storage_location, stock_quantity, hu_created
                        FROM stock_data
                        WHERE plant = %s AND storage_location = %s
                    """, (cleaned_plant, cleaned_storage_location))

                    existing_stocks = {}
                    for row in cursor.fetchall():
                        key = normalize_material_key(
                            row['material'],
                            row['batch'],
                            row['plant'],
                            row['storage_location']
                        )
                        existing_stocks[key] = row

                    # STEP 3: Process data dari SAP dengan LOGIKA YANG BENAR
                    inserted_count = 0
                    updated_count = 0
                    processed_keys = set()

                    for item in stock_data:
                        # Bersihkan data dari SAP
                        material = clean_sap_data(item.get('MATNR', ''))
                        material_description = clean_sap_data(item.get('MAKTX', ''))
                        plant_code = clean_sap_data(item.get('WERKS', ''))
                        storage_loc = clean_sap_data(item.get('LGORT', ''))
                        batch = clean_sap_data(item.get('CHARG', ''))
                        base_unit = clean_sap_data(item.get('MEINS', ''))
                        sales_document = clean_sap_data(item.get('VBELN', ''))
                        item_number = clean_sap_data(item.get('POSNR', ''))

                        vendor_name = item.get('NAME1', '')
                        if vendor_name:
                            vendor_name = ' '.join(vendor_name.split()[:2])
                        vendor_name = clean_sap_data(vendor_name)

                        # ✅ GUNAKAN DECIMAL, BUKAN INTEGER
                        sap_stock_quantity = safe_convert_to_decimal(item.get('CLABS', 0))

                        # Skip jika data essential kosong
                        if not material or not plant_code or not storage_loc:
                            logger.warning(f"Skipping record with missing essential data: material='{material}', plant='{plant_code}', storage_location='{storage_loc}'")
                            continue

                        # Buat key untuk material ini
                        stock_key = normalize_material_key(material, batch, plant_code, storage_loc)
                        processed_keys.add(stock_key)

                        # Cari existing stock untuk dapatkan ID
                        existing_stock = existing_stocks.get(stock_key)
                        stock_id = existing_stock['id'] if existing_stock else None

                        # ✅ LOGIKA PERHITUNGAN YANG BENAR:
                        # Stock Available = Stock SAP - Total HU yang sudah dibuat
                        hu_quantity = hu_totals.get(stock_key, Decimal('0'))
                        available_quantity = max(Decimal('0'), sap_stock_quantity - hu_quantity)

                        if hu_quantity > 0:
                            logger.info(f"Stock calculation for {material}: SAP={sap_stock_quantity}, HU={hu_quantity}, Available={available_quantity}")

                        # Gunakan ON DUPLICATE KEY UPDATE
                        sql = """
                        INSERT INTO stock_data
                        (material, material_description, plant, storage_location, batch, stock_quantity,
                         base_unit, sales_document, item_number, vendor_name, last_updated, created_at, updated_at)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                        material_description = VALUES(material_description),
                        stock_quantity = VALUES(stock_quantity),  -- Simpan available quantity
                        base_unit = VALUES(base_unit),
                        sales_document = VALUES(sales_document),
                        item_number = VALUES(item_number),
                        vendor_name = VALUES(vendor_name),
                        last_updated = VALUES(last_updated),
                        updated_at = NOW()
                        """

                        cursor.execute(sql, (
                            material,
                            material_description,
                            plant_code,
                            storage_loc,
                            batch,
                            available_quantity,  # ✅ SIMPAN AVAILABLE QUANTITY, BUKAN SAP QUANTITY
                            base_unit,
                            sales_document,
                            item_number,
                            vendor_name,
                            datetime.now()
                        ))

                        if cursor.rowcount == 1:
                            inserted_count += 1
                        else:
                            updated_count += 1

                    # STEP 4: Handle data yang tidak ada di SAP
                    deleted_count = 0
                    if processed_keys:
                        # Hapus data yang tidak ada di SAP dan tidak ada HU history
                        placeholders = ','.join(['%s'] * len(processed_keys))
                        delete_sql = f"""
                        DELETE FROM stock_data
                        WHERE plant = %s AND storage_location = %s
                        AND CONCAT(material, '_', COALESCE(batch, ''), '_', plant, '_', storage_location) NOT IN ({placeholders})
                        AND NOT EXISTS (
                            SELECT 1 FROM hu_histories
                            WHERE hu_histories.material = stock_data.material
                            AND COALESCE(hu_histories.batch, '') = COALESCE(stock_data.batch, '')
                            AND hu_histories.plant = stock_data.plant
                            AND hu_histories.storage_location = stock_data.storage_location
                        )
                        """
                        delete_params = [cleaned_plant, cleaned_storage_location] + list(processed_keys)
                        cursor.execute(delete_sql, delete_params)
                        deleted_count = cursor.rowcount

                    mysql_conn.commit()
                    # TANPA EMOJI - PERBAIKAN UTAMA
                    logger.info(f"Stock sync completed: {inserted_count} inserted, {updated_count} updated, {deleted_count} deleted for plant '{cleaned_plant}', storage location '{cleaned_storage_location}'")

            except Exception as e:
                logger.error(f"Error saving stock data to MySQL: {e}")
                logger.error(f"Error type: {type(e).__name__}")
                logger.error(f"Error details: {str(e)}")
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
        logger.error(f"Error type: {type(e).__name__}")
        return False
    finally:
        if conn:
            conn.close()

# ===== ENDPOINT SYNC STOCK MANUAL =====
@app.route('/stock/sync', methods=['POST', 'OPTIONS'])
def sync_stock():
    """Endpoint untuk manual stock sync dari Laravel"""
    if request.method == 'OPTIONS':
        return '', 200

    try:
        data = request.get_json()
        if not data:
            return jsonify({"success": False, "error": "No data provided"}), 400

        plant = data.get('plant', '3000')
        storage_location = data.get('storage_location', '3D10')

        logger.info(f"Manual stock sync requested for plant: {plant}, storage_location: {storage_location}")

        success = fetch_stock_from_sap(plant, storage_location)

        if success:
            return jsonify({
                "success": True,
                "message": f"Stock data synced successfully for plant {plant}, location {storage_location}"
            })
        else:
            return jsonify({
                "success": False,
                "error": "Failed to sync stock data from SAP"
            }), 500

    except Exception as e:
        logger.error(f"Error in stock sync endpoint: {e}")
        return jsonify({
            "success": False,
            "error": f"Sync error: {str(e)}"
        }), 500

# ===== ENDPOINT GET_STOCK YANG DIPERBAIKI =====
@app.route('/stock', methods=['GET'])
def get_stock():
    """Get stock data dengan perhitungan real-time available stock"""
    try:
        connection = get_mysql_connection()
        if not connection:
            return jsonify({"success": False, "error": "Database connection failed"}), 500

        with connection.cursor() as cursor:
            page = request.args.get('page', 1, type=int)
            per_page = min(request.args.get('per_page', 100, type=int), 1000)
            material_filter = request.args.get('material', '')
            plant_filter = request.args.get('plant', '')
            storage_location_filter = request.args.get('storage_location', '')
            offset = (page - 1) * per_page

            # Query dasar - stock_data sudah berisi available quantity
            query = """
            SELECT sd.*
            FROM stock_data sd
            WHERE 1=1
            """

            count_query = "SELECT COUNT(*) as total FROM stock_data sd WHERE 1=1"
            params = []
            count_params = []

            if material_filter:
                query += " AND sd.material LIKE %s"
                count_query += " AND sd.material LIKE %s"
                params.append(f"%{material_filter}%")
                count_params.append(f"%{material_filter}%")

            if plant_filter:
                query += " AND sd.plant = %s"
                count_query += " AND sd.plant = %s"
                params.append(plant_filter)
                count_params.append(plant_filter)

            if storage_location_filter:
                query += " AND sd.storage_location = %s"
                count_query += " AND sd.storage_location = %s"
                params.append(storage_location_filter)
                count_params.append(storage_location_filter)

            query += " ORDER BY sd.material, sd.plant, sd.storage_location, sd.batch LIMIT %s OFFSET %s"
            params.extend([per_page, offset])

            cursor.execute(query, params)
            stock_data = cursor.fetchall()

            cursor.execute(count_query, count_params)
            total_count = cursor.fetchone()['total']

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
    finally:
        if connection:
            connection.close()

# ===== SCHEDULER UNTUK AUTO-SYNC =====
def scheduled_sync():
    """Wrapper function untuk scheduler dengan error handling"""
    try:
        logger.info("Auto-sync started by scheduler")
        success = fetch_stock_from_sap()
        if success:
            logger.info("Auto-sync completed successfully")
        else:
            logger.error("Auto-sync failed")
    except Exception as e:
        logger.error(f"Error in auto-sync: {e}")

# Initialize scheduler
try:
    scheduler = BackgroundScheduler()
    scheduler.add_job(func=scheduled_sync, trigger="interval", minutes=5, id='auto_sync_job')
    scheduler.start()
    logger.info("Scheduler started successfully")
except Exception as e:
    logger.error(f"Failed to start scheduler: {e}")
    scheduler = None

atexit.register(lambda: scheduler.shutdown() if scheduler else None)

# ===== ROUTES HU CREATION =====
@app.route('/hu/create-single', methods=['POST', 'OPTIONS'])
def create_single_hu():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        if not data:
            return jsonify({"success": False, "error": "No data provided"}), 400

        logger.info(f"Creating single HU with data: { {k: v for k, v in data.items() if k != 'sap_password'} }")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")

        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        # Validasi required fields
        required_fields = ['hu_exid', 'material', 'plant', 'stge_loc', 'pack_qty']
        for field in required_fields:
            if not data.get(field):
                return jsonify({"success": False, "error": f"Missing required field: {field}"}), 400

        # Terapkan format material
        formatted_material = format_material(data.get('material'))
        formatted_pack_mat = format_material(data.get('pack_mat', ''))

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
                "BASE_UNIT_QTY": data.get('base_unit_qty') or '',
                "HU_ITEM_TYPE": "1",
                "BATCH": data.get('batch', ''),
                "SPEC_STOCK": "E",
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
def create_single_multi_hu():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        if not data:
            return jsonify({"success": False, "error": "No data provided"}), 400

        logger.info(f"Creating single HU with multiple materials")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")

        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        # Validasi required fields
        required_fields = ['hu_exid', 'plant', 'stge_loc', 'items']
        for field in required_fields:
            if not data.get(field):
                return jsonify({"success": False, "error": f"Missing required field: {field}"}), 400

        # Format items
        formatted_items = []
        for item in data.get('items', []):
            formatted_material = format_material(item.get('material'))
            formatted_items.append({
                "MATERIAL": formatted_material,
                "PLANT": data.get('plant'),
                "STGE_LOC": data.get('stge_loc'),
                "PACK_QTY": str(item.get('pack_qty', 0)),
                "BASE_UNIT_QTY": item.get('base_unit_qty') or '',
                "HU_ITEM_TYPE": "1",
                "BATCH": item.get('batch', ''),
                "SPEC_STOCK": "E",
                "SP_STCK_NO": item.get('sp_stck_no', ''),
                "GR_DATE": item.get('gr_date', '')
            })

        params = {
            "I_HU_EXID": data.get('hu_exid'),
            "I_PACK_MAT": format_material(data.get('pack_mat', '')),
            "I_PLANT": data.get('plant'),
            "I_STGE_LOC": data.get('stge_loc'),
            "T_ITEMS": formatted_items
        }

        logger.info(f"Calling ZRFC_CREATE_HU_EXT with {len(formatted_items)} items")
        result = conn.call('ZRFC_CREATE_HU_EXT', **params)
        conn.close()

        # Cek jika SAP mengembalikan error
        if 'E_RETURN' in result and result['E_RETURN'] and result['E_RETURN']['TYPE'] in ['E', 'A']:
            error_message = result['E_RETURN']['MESSAGE']
            logger.error(f"SAP Error creating single multi HU: {error_message}")
            return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

        logger.info(f"Single multi HU created successfully")
        return jsonify({
            "success": True,
            "data": result,
            "message": "Single HU with multiple materials created successfully"
        })

    except Exception as e:
        error_message = str(e)
        logger.error(f"Error creating single multi HU: {error_message}")
        return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

@app.route('/hu/create-multiple', methods=['POST', 'OPTIONS'])
def create_multiple_hu():
    if request.method == 'OPTIONS':
        return '', 200
    try:
        data = request.get_json()
        if not data:
            return jsonify({"success": False, "error": "No data provided"}), 400

        logger.info(f"Creating multiple HUs")

        sap_user = data.get('sap_user') or os.getenv("SAP_USER")
        sap_password = data.get('sap_password') or os.getenv("SAP_PASSWORD")

        if not sap_user or not sap_password:
            return jsonify({"success": False, "error": "SAP credentials missing"}), 400

        conn = connect_sap(sap_user, sap_password)
        if not conn:
            return jsonify({"success": False, "error": "Cannot connect to SAP system"}), 500

        results = []
        for hu_data in data.get('hus', []):
            try:
                # Validasi required fields untuk setiap HU
                required_fields = ['hu_exid', 'material', 'plant', 'stge_loc', 'pack_qty']
                for field in required_fields:
                    if not hu_data.get(field):
                        logger.error(f"Missing required field {field} for HU: {hu_data.get('hu_exid')}")
                        continue

                # Terapkan format material
                formatted_material = format_material(hu_data.get('material'))
                formatted_pack_mat = format_material(hu_data.get('pack_mat', ''))

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
                        "BASE_UNIT_QTY": hu_data.get('base_unit_qty') or '',
                        "HU_ITEM_TYPE": "1",
                        "BATCH": hu_data.get('batch', ''),
                        "SPEC_STOCK": "E",
                        "SP_STCK_NO": hu_data.get('sp_stck_no', ''),
                        "GR_DATE": hu_data.get('gr_date', '')
                    }]
                }

                logger.info(f"Calling ZRFC_CREATE_HU_EXT for HU: {hu_data.get('hu_exid')}")
                result = conn.call('ZRFC_CREATE_HU_EXT', **params)

                # Cek jika SAP mengembalikan error
                if 'E_RETURN' in result and result['E_RETURN'] and result['E_RETURN']['TYPE'] in ['E', 'A']:
                    error_message = result['E_RETURN']['MESSAGE']
                    logger.error(f"SAP Error creating HU {hu_data.get('hu_exid')}: {error_message}")
                    results.append({
                        "hu_exid": hu_data.get('hu_exid'),
                        "success": False,
                        "error": error_message
                    })
                else:
                    results.append({
                        "hu_exid": hu_data.get('hu_exid'),
                        "success": True,
                        "data": result
                    })
                    logger.info(f"HU {hu_data.get('hu_exid')} created successfully")

            except Exception as e:
                error_message = str(e)
                logger.error(f"Error creating HU {hu_data.get('hu_exid')}: {error_message}")
                results.append({
                    "hu_exid": hu_data.get('hu_exid'),
                    "success": False,
                    "error": error_message
                })

        conn.close()

        # Check if all failed
        success_count = sum(1 for r in results if r.get('success'))
        if success_count == 0:
            return jsonify({
                "success": False,
                "error": "All HU creations failed",
                "details": results
            }), 500

        return jsonify({
            "success": True,
            "data": results,
            "message": f"Successfully created {success_count} out of {len(results)} HUs"
        })

    except Exception as e:
        error_message = str(e)
        logger.error(f"Error creating multiple HUs: {error_message}")
        return jsonify({"success": False, "error": f"SAP Error: {error_message}"}), 500

# Health check endpoint
@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "healthy", "service": "SAP HU Automation API"})

@app.route('/', methods=['GET'])
def home():
    return jsonify({"message": "SAP HU Automation API is running"})

if __name__ == '__main__':
    # TANPA EMOJI - PERBAIKAN UTAMA
    logger.info("Starting SAP HU Automation API...")
    logger.info("Initializing database...")

    if init_database():
        logger.info("Database initialized successfully")
        logger.info("Starting initial stock data sync...")
        try:
            fetch_stock_from_sap()
            logger.info("Initial stock sync completed")
        except Exception as e:
            logger.error(f"Initial stock sync failed: {e}")
    else:
        logger.error("Database initialization failed")

    logger.info("Starting Flask application...")
    app.run(host='0.0.0.0', port=5000, debug=False)
