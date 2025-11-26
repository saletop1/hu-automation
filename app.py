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

# Setup logging
logging.basicConfig(
    level=logging.INFO,
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
    last_sync_status['last_attempt_time'] = datetime.now()
    last_sync_status['is_running'] = False

    if success:
        last_sync_status['last_success_time'] = datetime.now()
        last_sync_status['last_error'] = None
    else:
        last_sync_status['last_error'] = error

def auto_sync_job():
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
            update_sync_status(True)
        else:
            logger.error("Auto sync gagal")
            update_sync_status(False, "Auto sync gagal")

    except Exception as e:
        error_msg = f"Error dalam auto sync: {e}"
        logger.error(error_msg)
        update_sync_status(False, error_msg)

def start_scheduler():
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
    if scheduler.running:
        scheduler.shutdown()
        logger.info("Scheduler dihentikan")

atexit.register(stop_scheduler)

# ==================== FUNGSI KONEKSI SAP ====================

def connect_sap_with_credentials(sap_user, sap_password):
    try:
        default_user = os.getenv("SAP_USER", "auto_email")
        if not sap_user or sap_user == default_user:
            logger.error(f"SAP User dari request tidak valid: {sap_user}")
            return None, "SAP User tidak valid atau masih menggunakan default user"

        if not sap_password:
            logger.error("SAP Password dari request kosong")
            return None, "SAP Password tidak boleh kosong"

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
        logger.info(f"Koneksi SAP berhasil dengan user: {sap_user}")
        return conn, None

    except Exception as e:
        error_msg = f"Gagal koneksi SAP: {str(e)}"
        logger.error(f"{error_msg}")
        return None, error_msg

def connect_sap():
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
        logger.info("Koneksi SAP berhasil (environment credentials)")
        return conn

    except Exception as e:
        logger.error(f"Gagal koneksi SAP: {str(e)}")
        return None

def connect_mysql():
    try:
        conn = pymysql.connect(**DB_CONFIG)
        return conn
    except Exception as e:
        logger.error(f"Gagal koneksi MySQL: {e}")
        return None

def clean_value(value):
    if value is None:
        return ''
    return str(value).strip()

def convert_qty(value):
    try:
        if not value:
            return Decimal('0')
        return Decimal(str(value)).quantize(Decimal('0.001'))
    except:
        return Decimal('0')

def ensure_magry_column_exists():
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

def ensure_advanced_columns_exist():
    mysql_conn = connect_mysql()
    if not mysql_conn:
        return False

    try:
        with mysql_conn.cursor() as cursor:
            columns_to_add = [
                "is_active TINYINT DEFAULT 1",
                "sync_status VARCHAR(20) DEFAULT 'SYNCED'",
                "last_synced_at DATETIME",
                "reason VARCHAR(100)"
            ]

            for column_def in columns_to_add:
                column_name = column_def.split(' ')[0]
                cursor.execute(f"""
                    SELECT COUNT(*)
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'stock_data'
                    AND COLUMN_NAME = '{column_name}'
                """)
                result = cursor.fetchone()

                if result[0] == 0:
                    cursor.execute(f"ALTER TABLE stock_data ADD COLUMN {column_def}")
                    logger.info(f"Kolom {column_name} ditambahkan")

            cursor.execute("""
                SELECT COUNT(*)
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'stock_data'
                AND CONSTRAINT_NAME = 'idx_business_key'
            """)
            has_business_key = cursor.fetchone()[0] > 0

            if not has_business_key:
                cursor.execute("""
                    ALTER TABLE stock_data
                    ADD UNIQUE INDEX idx_business_key
                    (material, plant, storage_location, batch, sales_document, item_number)
                """)
                logger.info("Business key index ditambahkan")

            mysql_conn.commit()
            return True

    except Exception as e:
        logger.error(f"Error memastikan kolom: {e}")
        return False
    finally:
        mysql_conn.close()

# ==================== SYNC STOCK DATA DENGAN SOFT DELETE ====================

def extract_business_keys(stock_data):
    business_keys = set()

    for item in stock_data:
        material = clean_value(item.get('MATNR'))
        batch = clean_value(item.get('CHARG'))
        sales_doc = clean_value(item.get('VBELN'))
        item_number = clean_value(item.get('POSNR'))

        if material:
            key = f"{material}|{batch}|{sales_doc}|{item_number}"
            business_keys.add(key)

    return business_keys

def soft_delete_missing_records(mysql_conn, sap_business_keys, plant, storage_location):
    if not sap_business_keys:
        return 0

    try:
        with mysql_conn.cursor() as cursor:
            cursor.execute("""
                CREATE TEMPORARY TABLE temp_business_keys (
                    business_key VARCHAR(100) COLLATE utf8mb4_unicode_ci PRIMARY KEY
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            """)

            insert_data = [(key,) for key in sap_business_keys]
            if insert_data:
                cursor.executemany("INSERT INTO temp_business_keys (business_key) VALUES (%s)", insert_data)

            update_sql = """
                UPDATE stock_data sd
                SET sd.stock_quantity = 0,
                    sd.is_active = 0,
                    sd.last_updated = %s,
                    sd.last_synced_at = %s,
                    sd.sync_status = 'NOT_IN_SAP',
                    sd.reason = 'Stock tidak ditemukan di SAP'
                WHERE sd.plant = %s
                AND sd.storage_location = %s
                AND (sd.stock_quantity > 0 OR sd.is_active = 1)
                AND NOT EXISTS (
                    SELECT 1 FROM temp_business_keys tbk
                    WHERE tbk.business_key = CONCAT(
                        sd.material COLLATE utf8mb4_unicode_ci, '|',
                        COALESCE(sd.batch, '') COLLATE utf8mb4_unicode_ci, '|',
                        COALESCE(sd.sales_document, '') COLLATE utf8mb4_unicode_ci, '|',
                        COALESCE(sd.item_number, '') COLLATE utf8mb4_unicode_ci
                    )
                )
            """

            cursor.execute(update_sql, [datetime.now(), datetime.now(), plant, storage_location])
            affected_rows = cursor.rowcount

            cursor.execute("DROP TEMPORARY TABLE temp_business_keys")

            if affected_rows > 0:
                logger.info(f"Marked {affected_rows} records sebagai inactive")

            return affected_rows

    except Exception as e:
        logger.error(f"Error dalam soft_delete_missing_records: {e}")
        return 0

def handle_empty_sap_data(mysql_conn, plant, storage_location):
    try:
        with mysql_conn.cursor() as cursor:
            update_sql = """
                UPDATE stock_data
                SET stock_quantity = 0,
                    is_active = 0,
                    last_updated = %s,
                    last_synced_at = %s,
                    sync_status = 'LOCATION_EMPTY',
                    reason = 'Storage location kosong di SAP'
                WHERE plant = %s
                AND storage_location = %s
                AND (stock_quantity > 0 OR is_active = 1)
            """

            cursor.execute(update_sql, [datetime.now(), datetime.now(), plant, storage_location])
            affected_rows = cursor.rowcount

            if affected_rows > 0:
                logger.info(f"Semua stock di {plant}/{storage_location} di-set ke 0")
            else:
                logger.info(f"Tidak ada data aktif di {plant}/{storage_location}")

            mysql_conn.commit()
            return True

    except Exception as e:
        logger.error(f"Error dalam handle_empty_sap_data: {e}")
        return False

def upsert_sap_data(mysql_conn, stock_data, plant, storage_location):
    inserted = 0
    updated = 0

    try:
        with mysql_conn.cursor() as cursor:
            for item in stock_data:
                material = clean_value(item.get('MATNR'))
                desc = clean_value(item.get('MAKTX'))
                qty = convert_qty(item.get('CLABS'))
                magrv = clean_value(item.get('MAGRV'))
                sales_document = clean_value(item.get('VBELN'))
                item_number = clean_value(item.get('POSNR'))
                vendor_name = clean_value(item.get('NAME1'))
                batch = clean_value(item.get('CHARG'))
                base_unit = clean_value(item.get('MEINS', 'PCS'))

                if not material:
                    continue

                sql = """
                INSERT INTO stock_data
                (material, material_description, plant, storage_location, batch,
                 stock_quantity, base_unit, magry, sales_document, item_number,
                 vendor_name, last_updated, last_synced_at, is_active, sync_status, reason)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                material_description = VALUES(material_description),
                stock_quantity = VALUES(stock_quantity),
                base_unit = VALUES(base_unit),
                magry = VALUES(magry),
                sales_document = VALUES(sales_document),
                item_number = VALUES(item_number),
                vendor_name = VALUES(vendor_name),
                last_updated = VALUES(last_updated),
                last_synced_at = VALUES(last_synced_at),
                is_active = 1,
                sync_status = 'SYNCED',
                reason = NULL
                """

                cursor.execute(sql, (
                    material, desc, plant, storage_location, batch,
                    float(qty), base_unit, magrv,
                    sales_document, item_number, vendor_name,
                    datetime.now(), datetime.now(), 1, 'SYNCED', None
                ))

                if cursor.rowcount == 1:
                    inserted += 1
                else:
                    updated += 1

            return inserted, updated

    except Exception as e:
        logger.error(f"Error dalam upsert_sap_data: {e}")
        raise e

def sync_stock_data(plant='3000', storage_location='3D10'):
    sap_conn = None
    mysql_conn = None

    try:
        sap_conn = connect_sap()
        if not sap_conn:
            logger.error("Tidak dapat melanjutkan sync karena koneksi SAP gagal")
            return False

        mysql_conn = connect_mysql()
        if not mysql_conn:
            logger.error("Koneksi database gagal")
            return False

        result = sap_conn.call('Z_FM_YMMR006NX',
                             P_WERKS=plant,
                             P_MTART='FERT',
                             P_LGORT=storage_location)

        stock_data = result.get('T_DATA', [])

        if not stock_data:
            logger.info("Tidak ada data dari SAP")
            return handle_empty_sap_data(mysql_conn, plant, storage_location)

        sap_business_keys = extract_business_keys(stock_data)
        logger.info(f"Data dari SAP: {len(sap_business_keys)} unique business keys")

        marked_inactive = soft_delete_missing_records(mysql_conn, sap_business_keys, plant, storage_location)
        inserted, updated = upsert_sap_data(mysql_conn, stock_data, plant, storage_location)

        mysql_conn.commit()

        logger.info(f"Sync selesai: {inserted} baru, {updated} update, {marked_inactive} di-nonaktifkan")
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

# ==================== FUNGSI VIEW STOCK DATA ====================

def get_active_stock_data(plant='3000', storage_location='3D10', include_inactive=False):
    mysql_conn = connect_mysql()
    if not mysql_conn:
        return None

    try:
        with mysql_conn.cursor(pymysql.cursors.DictCursor) as cursor:
            if include_inactive:
                sql = """
                    SELECT * FROM stock_data
                    WHERE plant = %s AND storage_location = %s
                    ORDER BY material, batch
                """
                cursor.execute(sql, (plant, storage_location))
            else:
                sql = """
                    SELECT * FROM stock_data
                    WHERE plant = %s AND storage_location = %s
                    AND is_active = 1 AND stock_quantity > 0
                    ORDER BY material, batch
                """
                cursor.execute(sql, (plant, storage_location))

            return cursor.fetchall()

    except Exception as e:
        logger.error(f"Error mengambil data stock: {e}")
        return None
    finally:
        mysql_conn.close()

# ==================== FUNGSI UTILITY UNTUK HU CREATION ====================

def validate_stock_availability(material, plant, storage_location, required_qty, batch=None):
    """
    Validasi ketersediaan stock sebelum membuat HU
    """
    mysql_conn = connect_mysql()
    if not mysql_conn:
        return False, "Database connection error"

    try:
        with mysql_conn.cursor(pymysql.cursors.DictCursor) as cursor:
            # Query stock yang tersedia
            if batch:
                sql = """
                    SELECT SUM(stock_quantity) as total_stock
                    FROM stock_data
                    WHERE material = %s
                    AND plant = %s
                    AND storage_location = %s
                    AND batch = %s
                    AND is_active = 1
                    AND stock_quantity > 0
                """
                cursor.execute(sql, (material, plant, storage_location, batch))
            else:
                sql = """
                    SELECT SUM(stock_quantity) as total_stock
                    FROM stock_data
                    WHERE material = %s
                    AND plant = %s
                    AND storage_location = %s
                    AND is_active = 1
                    AND stock_quantity > 0
                """
                cursor.execute(sql, (material, plant, storage_location))

            result = cursor.fetchone()
            available_stock = result['total_stock'] if result and result['total_stock'] else 0

            if available_stock >= required_qty:
                return True, f"Stock tersedia: {available_stock}, dibutuhkan: {required_qty}"
            else:
                return False, f"Stock tidak mencukupi. Tersedia: {available_stock}, Dibutuhkan: {required_qty}"

    except Exception as e:
        logger.error(f"Error validasi stock: {e}")
        return False, f"Error validasi stock: {str(e)}"
    finally:
        if mysql_conn:
            mysql_conn.close()

def clean_hu_parameters(hu_data):
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

    # Handle pack_qty
    if 'pack_qty' in cleaned:
        try:
            pack_qty_str = str(cleaned['pack_qty']).strip()
            if not pack_qty_str:
                raise ValueError("Pack Qty tidak boleh kosong")
            pack_qty = float(pack_qty_str)
            if pack_qty <= 0:
                raise ValueError("Pack Qty harus lebih besar dari 0")
            cleaned['pack_qty'] = pack_qty
        except (ValueError, TypeError):
            raise ValueError(f"pack_qty harus berupa angka: {cleaned['pack_qty']}")

    # Handle base_unit_qty - PERBAIKAN DI SINI
    base_unit_qty_value = cleaned.get('base_unit_qty')

    # Jika base_unit_qty ada dan valid, gunakan itu
    if base_unit_qty_value is not None and base_unit_qty_value != '':
        try:
            base_unit_qty_str = str(base_unit_qty_value).strip()
            if base_unit_qty_str:  # Jika tidak empty string
                base_unit_qty = float(base_unit_qty_str)
                if base_unit_qty <= 0:
                    raise ValueError("Base Unit Qty harus lebih besar dari 0")
                cleaned['base_unit_qty'] = base_unit_qty
            else:
                # Jika base_unit_qty adalah empty string, use pack_qty
                cleaned['base_unit_qty'] = cleaned['pack_qty']
        except (ValueError, TypeError):
            # Jika base_unit_qty invalid, fallback to pack_qty
            cleaned['base_unit_qty'] = cleaned['pack_qty']
    else:
        # Jika base_unit_qty tidak provided atau None, use pack_qty
        cleaned['base_unit_qty'] = cleaned['pack_qty']

    optional_fields = ['batch', 'sp_stck_no']
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
    material = cleaned_data['material']
    if material.isdigit() and len(material) < 18:
        material = material.zfill(18)

    batch = cleaned_data.get('batch', '')
    if batch is None:
        batch = ''

    sp_stck_no = cleaned_data.get('sp_stck_no', '')
    if sp_stck_no is None:
        sp_stck_no = ''

    # Gunakan base_unit_qty jika valid, otherwise pack_qty
    quantity_to_use = cleaned_data.get('base_unit_qty')
    if quantity_to_use is None or quantity_to_use <= 0:
        quantity_to_use = cleaned_data['pack_qty']

    if quantity_to_use <= 0:
        raise ValueError(f"Quantity harus lebih besar dari 0, got: {quantity_to_use}")

    quantity_str = f"{quantity_to_use:.3f}".rstrip('0').rstrip('.') if '.' in f"{quantity_to_use}" else f"{quantity_to_use}"

    # HANYA GUNAKAN PACK_QTY
    item = {
        "MATERIAL": material,
        "PLANT": cleaned_data['plant'],
        "STGE_LOC": cleaned_data['stge_loc'],
        "PACK_QTY": quantity_str,
        "HU_ITEM_TYPE": "1",
        "BATCH": batch,
        "SPEC_STOCK": "E" if sp_stck_no else "",
        "SP_STCK_NO": sp_stck_no
    }

    return [item]

def validate_sap_response(result):
    if not result:
        return False, "Tidak ada response dari SAP RFC call"

    logger.info(f"SAP Response keys: {list(result.keys())}")

    # Cek E_RETURN untuk error/warning
    if 'E_RETURN' in result and result['E_RETURN']:
        error_type = result['E_RETURN'].get('TYPE', '')
        error_message = result['E_RETURN'].get('MESSAGE', 'Unknown SAP error')
        error_id = result['E_RETURN'].get('ID', '')
        error_number = result['E_RETURN'].get('NUMBER', '')

        logger.info(f"SAP Return - Type: {error_type}, ID: {error_id}, Number: {error_number}, Message: {error_message}")

        if error_type in ['E', 'A']:  # Error atau Abort
            return False, f"SAP Error: {error_message}"
        elif error_type == 'W':  # Warning
            logger.warning(f"SAP Warning: {error_message}")
            # Warning tidak menghalangi pembuatan HU, lanjutkan pengecekan E_HUKEY
        elif error_type == 'S':  # Success
            logger.info(f"SAP Success: {error_message}")
        elif error_type == 'I':  # Information
            logger.info(f"SAP Info: {error_message}")

    # Cek E_HUKEY - ini yang utama
    e_hukey = result.get('E_HUKEY')
    if e_hukey:
        hu_number = str(e_hukey).strip()
        hu_number_clean = hu_number.lstrip('0') or '0'

        # Validasi HU number
        if hu_number_clean and hu_number_clean != '0':
            logger.info(f"E_HUKEY valid: {hu_number} -> {hu_number_clean}")
            return True, f"HU berhasil dibuat: {hu_number_clean}"
        else:
            logger.warning(f"E_HUKEY tidak valid: '{hu_number}'")

    # Cek T_ITEMS untuk status item
    t_items = result.get('T_ITEMS', [])
    if t_items:
        for i, item in enumerate(t_items):
            item_status = item.get('STATS', '')
            item_material = item.get('MATERIAL', '')
            logger.info(f"T_ITEMS[{i}] - Material: {item_material}, Status: {item_status}")

            # Jika ada status success di T_ITEMS, anggap berhasil
            if 'Sukses' in item_status or 'Success' in item_status:
                logger.info(f"Item {i} status menunjukkan success")
                if e_hukey:
                    hu_number_clean = str(e_hukey).lstrip('0') or '0'
                    return True, f"HU berhasil dibuat: {hu_number_clean}"
                else:
                    return True, "HU berhasil dibuat (nomor HU tidak tersedia)"

    # Cek E_MESSAGE
    if result.get('E_MESSAGE'):
        e_message = result['E_MESSAGE']
        logger.info(f"E_MESSAGE: {e_message}")
        if e_message and 'success' in e_message.lower():
            return True, f"HU berhasil dibuat: {e_message}"

    # Jika tidak ada E_HUKEY tapi juga tidak ada error, mungkin success dengan cara lain
    if not result.get('E_RETURN') or (result.get('E_RETURN') and result['E_RETURN'].get('TYPE') in ['S', 'W', 'I']):
        logger.info("Tidak ada E_HUKEY tetapi tidak ada error yang fatal, mungkin HU berhasil dibuat")
        return True, "HU berhasil dibuat (konfirmasi melalui sistem SAP)"

    logger.warning(f"Tidak ada E_HUKEY dalam response. Full response: {result}")
    return False, "Gagal membuat HU - sistem SAP tidak mengembalikan nomor HU"

# ==================== CREATE HU FUNCTIONS ====================

def create_single_hu(data):
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty', 'sap_user', 'sap_password']
    for field in required_fields:
        if field not in data or not data[field]:
            error_msg = f"Field wajib {field} tidak ditemukan atau kosong"
            logger.error(f"{error_msg}")
            return {"success": False, "error": error_msg}, 400

    # Validasi quantity - PERBAIKAN DI SINI
    pack_qty = data.get('pack_qty')
    if pack_qty is None or pack_qty == '':
        error_msg = "pack_qty tidak boleh kosong"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    try:
        float(pack_qty)
    except (ValueError, TypeError):
        error_msg = f"pack_qty harus berupa angka: {pack_qty}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    # base_unit_qty tidak wajib, jadi tidak perlu validasi ketat
    base_unit_qty = data.get('base_unit_qty')
    if base_unit_qty is not None and base_unit_qty != '':
        try:
            float(base_unit_qty)
        except (ValueError, TypeError):
            error_msg = f"base_unit_qty harus berupa angka: {base_unit_qty}"
            logger.error(f"{error_msg}")
            return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Create Single HU - User: {sap_user}, HU: {data['hu_exid']}")

    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 401

    try:
        cleaned_data = clean_hu_parameters(data)
        items = prepare_hu_items(cleaned_data)

        # Validasi final items - PERBAIKAN DI SINI
        for item in items:
            pack_qty_val = item.get('PACK_QTY', '')

            if not pack_qty_val or pack_qty_val == '0':
                error_msg = f"PACK_QTY tidak valid: '{pack_qty_val}'"
                logger.error(f"{error_msg}")
                return {"success": False, "error": error_msg}, 400

        params = {
            "I_HU_EXID": cleaned_data['hu_exid'],
            "I_PACK_MAT": cleaned_data['pack_mat'],
            "I_PLANT": cleaned_data['plant'],
            "I_STGE_LOC": cleaned_data['stge_loc'],
            "T_ITEMS": items
        }

        logger.info(f"Memanggil RFC ZRFC_CREATE_HU_EXT dengan HU: {cleaned_data['hu_exid']}")
        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        success, message = validate_sap_response(result)

        if success:
            hu_number = result.get('E_HUKEY', '')
            hu_number_clean = hu_number.lstrip('0') or '0'

            logger.info(f"HU berhasil dibuat: {hu_number_clean}")
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number_clean,
                "hu_exid": cleaned_data['hu_exid']
            }, 200
        else:
            logger.error(f"Gagal membuat HU: {message}")
            return {"success": False, "error": message}, 400

    except ValueError as e:
        error_msg = f"Data tidak valid: {str(e)}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400
    except Exception as e:
        error_msg = f"Error buat HU: {str(e)}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

def create_single_multi_hu(data):
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'items', 'sap_user', 'sap_password']
    for field in required_fields:
        if not data.get(field):
            error_msg = f"Field {field} tidak boleh kosong"
            logger.error(f"{error_msg}")
            return {"success": False, "error": error_msg}, 400

    if not isinstance(data['items'], list) or len(data['items']) == 0:
        error_msg = "Items harus berupa array tidak kosong"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Create Single Multi HU - User: {sap_user}, HU: {data['hu_exid']}")

    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 401

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
                    logger.error(f"{error_msg}")
                    return {"success": False, "error": error_msg}, 400

                # Validasi quantity item sebelum cleaning
                pack_qty = item.get('pack_qty')
                if pack_qty is None or pack_qty == '':
                    error_msg = f"Item {i+1}: pack_qty tidak boleh kosong"
                    logger.error(f"{error_msg}")
                    return {"success": False, "error": error_msg}, 400

                try:
                    float(pack_qty)
                except (ValueError, TypeError):
                    error_msg = f"Item {i+1}: pack_qty harus berupa angka: {pack_qty}"
                    logger.error(f"{error_msg}")
                    return {"success": False, "error": error_msg}, 400

                cleaned_item = clean_hu_parameters({
                    'hu_exid': main_data['hu_exid'],
                    'pack_mat': main_data['pack_mat'],
                    'plant': main_data['plant'],
                    'stge_loc': main_data['stge_loc'],
                    'material': item.get('material'),
                    'pack_qty': item.get('pack_qty'),
                    'base_unit_qty': item.get('base_unit_qty'),
                    'batch': item.get('batch', ''),
                    'sp_stck_no': item.get('sp_stck_no', '')
                })

                # Prepare material
                material = cleaned_item['material']
                if material.isdigit() and len(material) < 18:
                    material = material.zfill(18)

                # Gunakan base_unit_qty jika valid, otherwise pack_qty
                quantity_to_use = cleaned_item.get('base_unit_qty')
                if quantity_to_use is None or quantity_to_use <= 0:
                    quantity_to_use = cleaned_item['pack_qty']

                if quantity_to_use <= 0:
                    error_msg = f"Item {i+1}: Quantity harus lebih besar dari 0, got: {quantity_to_use}"
                    logger.error(f"{error_msg}")
                    return {"success": False, "error": error_msg}, 400

                quantity_str = f"{quantity_to_use:.3f}".rstrip('0').rstrip('.') if '.' in f"{quantity_to_use}" else f"{quantity_to_use}"

                logger.info(f"Item {i+1} - Material: {material}, Qty: {quantity_str}")

                items.append({
                    "MATERIAL": material,
                    "PLANT": cleaned_item['plant'],
                    "STGE_LOC": cleaned_item['stge_loc'],
                    "PACK_QTY": quantity_str,
                    "HU_ITEM_TYPE": "1",
                    "BATCH": cleaned_item['batch'],
                    "SPEC_STOCK": "E" if cleaned_item['sp_stck_no'] else "",
                    "SP_STCK_NO": cleaned_item['sp_stck_no']
                })

            except ValueError as e:
                error_msg = f"Item {i+1}: {str(e)}"
                logger.error(f"{error_msg}")
                return {"success": False, "error": error_msg}, 400

        logger.info(f"Prepared {len(items)} items untuk HU {main_data['hu_exid']}")

        # Validasi final items sebelum kirim ke SAP
        for i, item in enumerate(items):
            pack_qty_val = item.get('PACK_QTY', '')

            if not pack_qty_val or pack_qty_val == '0':
                error_msg = f"Item {i+1}: PACK_QTY tidak valid: '{pack_qty_val}'"
                logger.error(f"{error_msg}")
                return {"success": False, "error": error_msg}, 400

        params = {
            "I_HU_EXID": main_data['hu_exid'],
            "I_PACK_MAT": main_data['pack_mat'],
            "I_PLANT": main_data['plant'],
            "I_STGE_LOC": main_data['stge_loc'],
            "T_ITEMS": items
        }

        logger.info(f"Memanggil RFC ZRFC_CREATE_HU_EXT untuk Multi-Item HU")
        logger.info(f"Items yang dikirim: {items}")
        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

        # Debug response SAP
        logger.info(f"SAP Response: {result}")

        success, message = validate_sap_response(result)

        if success:
            hu_number = result.get('E_HUKEY', '')
            hu_number_clean = hu_number.lstrip('0') or '0'

            logger.info(f"HU Multi berhasil dibuat: {hu_number_clean}")
            return {
                "success": True,
                "message": message,
                "data": result,
                "created_hu": hu_number_clean,
                "hu_exid": main_data['hu_exid'],
                "items_count": len(items)
            }, 200
        else:
            logger.error(f"Gagal membuat HU Multi: {message}")
            return {"success": False, "error": message}, 400

    except Exception as e:
        error_msg = f"Error buat HU multi: {str(e)}"
        logger.error(f"{error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

def create_multiple_hus(data):
    if 'hus' not in data or not isinstance(data['hus'], list):
        error_msg = "Data 'hus' harus berupa array"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    if len(data['hus']) == 0:
        error_msg = "Data 'hus' tidak boleh kosong"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    if 'sap_user' not in data or 'sap_password' not in data:
        error_msg = "SAP credentials (sap_user, sap_password) wajib diisi"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Create Multiple HUs - User: {sap_user}, Total: {len(data['hus'])}")

    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 401

    try:
        results = []
        success_count = 0

        for i, hu_data in enumerate(data['hus']):
            hu_exid = hu_data.get('hu_exid', f'HU_{i+1}')
            logger.info(f"Memproses HU {i+1}/{len(data['hus'])}: {hu_exid}")

            try:
                required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
                missing_fields = [field for field in required_fields if field not in hu_data or not hu_data[field]]

                if missing_fields:
                    error_msg = f"HU {i+1}: Field wajib {', '.join(missing_fields)} tidak ditemukan"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                # Validasi quantity sebelum cleaning
                pack_qty = hu_data.get('pack_qty')
                if pack_qty is None or pack_qty == '':
                    error_msg = f"HU {i+1}: pack_qty tidak boleh kosong"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                try:
                    float(pack_qty)
                except (ValueError, TypeError):
                    error_msg = f"HU {i+1}: pack_qty harus berupa angka: {pack_qty}"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                cleaned_data = clean_hu_parameters(hu_data)

                # Prepare material
                material = cleaned_data['material']
                if material.isdigit() and len(material) < 18:
                    material = material.zfill(18)

                batch = cleaned_data.get('batch', '')
                sp_stck_no = cleaned_data.get('sp_stck_no', '')

                # Gunakan base_unit_qty jika valid, otherwise pack_qty
                quantity_to_use = cleaned_data.get('base_unit_qty')
                if quantity_to_use is None or quantity_to_use <= 0:
                    quantity_to_use = cleaned_data['pack_qty']

                if quantity_to_use <= 0:
                    error_msg = f"HU {i+1}: Quantity harus lebih besar dari 0, got: {quantity_to_use}"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                quantity_str = f"{quantity_to_use:.3f}".rstrip('0').rstrip('.') if '.' in f"{quantity_to_use}" else f"{quantity_to_use}"

                logger.info(f"HU {i+1} - Material: {material}, Qty: {quantity_str}")

                items = [{
                    "MATERIAL": material,
                    "PLANT": cleaned_data['plant'],
                    "STGE_LOC": cleaned_data['stge_loc'],
                    "PACK_QTY": quantity_str,
                    "HU_ITEM_TYPE": "1",
                    "BATCH": batch,
                    "SPEC_STOCK": "E" if sp_stck_no else "",
                    "SP_STCK_NO": sp_stck_no
                }]

                # Validasi final items sebelum kirim ke SAP
                pack_qty_val = items[0].get('PACK_QTY', '')
                if not pack_qty_val or pack_qty_val == '0':
                    error_msg = f"HU {i+1}: PACK_QTY tidak valid: '{pack_qty_val}'"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                params = {
                    "I_HU_EXID": cleaned_data['hu_exid'],
                    "I_PACK_MAT": cleaned_data['pack_mat'],
                    "I_PLANT": cleaned_data['plant'],
                    "I_STGE_LOC": cleaned_data['stge_loc'],
                    "T_ITEMS": items
                }

                logger.info(f"Memanggil RFC untuk HU {i+1}: {cleaned_data['hu_exid']}")
                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                success, message = validate_sap_response(result)

                if success:
                    hu_number = result.get('E_HUKEY', '')
                    hu_number_clean = hu_number.lstrip('0') or '0'

                    logger.info(f"HU {i+1} berhasil: {hu_number_clean}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": True,
                        "message": message,
                        "created_hu": hu_number_clean,
                        "data": result
                    })
                    success_count += 1
                else:
                    logger.error(f"HU {i+1} gagal: {message}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": False,
                        "error": message
                    })

            except ValueError as e:
                error_msg = f"Data tidak valid: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg
                })
            except Exception as e:
                error_msg = f"Error processing HU: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                logger.error(traceback.format_exc())
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg
                })

        logger.info(f"Batch completed - Total: {len(results)}, Berhasil: {success_count}, Gagal: {len(results) - success_count}")

        summary = {
            "total": len(results),
            "success": success_count,
            "failed": len(results) - success_count
        }

        if success_count == 0:
            return {
                "success": False,
                "message": f"Semua {len(results)} HU gagal dibuat",
                "results": results,
                "summary": summary
            }, 400
        elif success_count == len(results):
            return {
                "success": True,
                "message": f"Semua {len(results)} HU berhasil dibuat",
                "results": results,
                "summary": summary
            }, 200
        else:
            return {
                "success": True,
                "message": f"Processed {len(results)} HUs, {success_count} successful, {len(results) - success_count} failed",
                "results": results,
                "summary": summary
            }, 207

    except Exception as e:
        error_msg = f"Error buat multiple HU: {str(e)}"
        logger.error(f"{error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

def create_multiple_hus_from_stock(data):
    """
    Create multiple HUs dari stock yang tersedia dengan validasi
    Khusus untuk skenario 1 HU = 1 material = 1 PC
    """
    required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'total_hu_needed', 'sap_user', 'sap_password']

    # Validasi input
    for field in required_fields:
        if not data.get(field):
            error_msg = f"Field {field} tidak boleh kosong"
            logger.error(f"{error_msg}")
            return {"success": False, "error": error_msg}, 400

    # Validasi total_hu_needed
    try:
        total_hu_needed = int(data['total_hu_needed'])
        if total_hu_needed <= 0:
            raise ValueError("Total HU harus lebih besar dari 0")
    except (ValueError, TypeError):
        error_msg = f"total_hu_needed harus berupa angka positif: {data['total_hu_needed']}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    # Validasi credentials
    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Create Multiple HUs from Stock - User: {sap_user}, Material: {data['material']}, Total HU: {total_hu_needed}")

    # 1. Validasi stock tersedia
    material = data['material']
    plant = data['plant']
    storage_location = data['stge_loc']
    batch = data.get('batch')

    logger.info(f"Validasi stock untuk {material} di {plant}/{storage_location}")

    stock_available, stock_message = validate_stock_availability(
        material, plant, storage_location, total_hu_needed, batch
    )

    if not stock_available:
        error_msg = f"Validasi stock gagal: {stock_message}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Validasi stock berhasil: {stock_message}")

    # 2. Generate HU data
    hus_data = generate_hus_from_single_material(data, total_hu_needed)

    logger.info(f"Generated {len(hus_data)} HUs untuk material {material}")

    # 3. Proses pembuatan HU
    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 401

    try:
        results = []
        success_count = 0

        for i, hu_data in enumerate(hus_data):
            hu_exid = hu_data['hu_exid']
            logger.info(f"Memproses HU {i+1}/{len(hus_data)}: {hu_exid}")

            try:
                # Prepare data untuk SAP call
                cleaned_data = clean_hu_parameters(hu_data)

                # Prepare material
                material = cleaned_data['material']
                if material.isdigit() and len(material) < 18:
                    material = material.zfill(18)

                batch = cleaned_data.get('batch', '')
                sp_stck_no = cleaned_data.get('sp_stck_no', '')

                # Quantity tetap 1 PC
                quantity_str = "1"

                items = [{
                    "MATERIAL": material,
                    "PLANT": cleaned_data['plant'],
                    "STGE_LOC": cleaned_data['stge_loc'],
                    "PACK_QTY": quantity_str,
                    "HU_ITEM_TYPE": "1",
                    "BATCH": batch,
                    "SPEC_STOCK": "E" if sp_stck_no else "",
                    "SP_STCK_NO": sp_stck_no
                }]

                params = {
                    "I_HU_EXID": cleaned_data['hu_exid'],
                    "I_PACK_MAT": cleaned_data['pack_mat'],
                    "I_PLANT": cleaned_data['plant'],
                    "I_STGE_LOC": cleaned_data['stge_loc'],
                    "T_ITEMS": items
                }

                logger.info(f"Memanggil RFC untuk HU {i+1}: {cleaned_data['hu_exid']}")
                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                success, message = validate_sap_response(result)

                if success:
                    hu_number = result.get('E_HUKEY', '')
                    hu_number_clean = hu_number.lstrip('0') or '0'

                    logger.info(f"HU {i+1} berhasil: {hu_number_clean}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": True,
                        "message": message,
                        "created_hu": hu_number_clean,
                        "material": material,
                        "quantity": 1
                    })
                    success_count += 1
                else:
                    logger.error(f"HU {i+1} gagal: {message}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": False,
                        "error": message,
                        "material": material,
                        "quantity": 1
                    })

            except ValueError as e:
                error_msg = f"Data tidak valid: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg,
                    "material": material,
                    "quantity": 1
                })
            except Exception as e:
                error_msg = f"Error processing HU: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                logger.error(traceback.format_exc())
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg,
                    "material": material,
                    "quantity": 1
                })

        logger.info(f"Batch completed - Total: {len(results)}, Berhasil: {success_count}, Gagal: {len(results) - success_count}")

        summary = {
            "total": len(results),
            "success": success_count,
            "failed": len(results) - success_count,
            "material": data['material'],
            "total_quantity": success_count,  # Total PC yang berhasil dibuat
            "requested_quantity": total_hu_needed
        }

        if success_count == 0:
            return {
                "success": False,
                "message": f"Semua {len(results)} HU gagal dibuat",
                "results": results,
                "summary": summary
            }, 400
        elif success_count == len(results):
            return {
                "success": True,
                "message": f"Semua {len(results)} HU berhasil dibuat",
                "results": results,
                "summary": summary
            }, 200
        else:
            return {
                "success": True,
                "message": f"Processed {len(results)} HUs, {success_count} successful, {len(results) - success_count} failed",
                "results": results,
                "summary": summary
            }, 207

    except Exception as e:
        error_msg = f"Error buat multiple HU dari stock: {str(e)}"
        logger.error(f"{error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

# ==================== ROUTES API ====================
def generate_hus_from_single_material(base_data, total_hu_needed):
    """
    Generate multiple HUs dari single material dengan quantity 1 PC per HU
    """
    hus = []

    # Base HU external ID (10 digit)
    base_hu_exid = base_data['hu_exid']

    for i in range(total_hu_needed):
        # Generate sequential HU external ID
        hu_exid = str(int(base_hu_exid) + i).zfill(10)

        hu_data = {
            'hu_exid': hu_exid,
            'pack_mat': base_data['pack_mat'],
            'plant': base_data['plant'],
            'stge_loc': base_data['stge_loc'],
            'material': base_data['material'],
            'pack_qty': '1',  # 1 PC per HU
            'base_unit_qty': '1',  # 1 PC per HU
            'batch': base_data.get('batch', ''),
            'sp_stck_no': base_data.get('sp_stck_no', ''),
            'sap_user': base_data['sap_user'],
            'sap_password': base_data['sap_password']
        }

        hus.append(hu_data)

    return hus

@app.route('/hu/create-single', methods=['POST'])
def api_create_single():
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("/hu/create-single dipanggil")
    result, status_code = create_single_hu(data)
    return jsonify(result), status_code

@app.route('/hu/create-single-multi', methods=['POST'])
def api_create_single_multi():
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("/hu/create-single-multi dipanggil")
    result, status_code = create_single_multi_hu(data)
    return jsonify(result), status_code

@app.route('/hu/create-multiple-from-stock', methods=['POST'])
def api_create_multiple_from_stock():
    """
    Endpoint khusus untuk membuat multiple HUs dari single material
    dengan validasi stock dan 1 HU = 1 PC
    """
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("/hu/create-multiple-from-stock dipanggil")

    # Validasi additional fields
    if 'total_hu_needed' not in data:
        return jsonify({"success": False, "error": "total_hu_needed wajib diisi"}), 400

    result, status_code = create_multiple_hus_from_stock(data)
    return jsonify(result), status_code
@app.route('/stock/check-availability', methods=['POST'])
def api_check_stock_availability():
    """
    Endpoint untuk mengecek ketersediaan stock sebelum membuat HU
    """
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    required_fields = ['material', 'plant', 'storage_location', 'required_qty']
    for field in required_fields:
        if not data.get(field):
            return jsonify({"success": False, "error": f"Field {field} wajib diisi"}), 400

    try:
        material = data['material']
        plant = data['plant']
        storage_location = data['storage_location']
        required_qty = int(data['required_qty'])
        batch = data.get('batch')

        available, message = validate_stock_availability(
            material, plant, storage_location, required_qty, batch
        )

        return jsonify({
            "success": True,
            "data": {
                "material": material,
                "plant": plant,
                "storage_location": storage_location,
                "required_quantity": required_qty,
                "stock_available": available,
                "message": message
            }
        }), 200

    except Exception as e:
        return jsonify({
            "success": False,
            "error": f"Error checking stock: {str(e)}"
        }), 500

@app.route('/hu/create-multiple', methods=['POST'])
def api_create_multiple():
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("/hu/create-multiple dipanggil")
    result, status_code = create_multiple_hus(data)
    return jsonify(result), status_code
# ==================== ROUTES API ====================

@app.route('/hu/create-multiple-flexible', methods=['POST'])
def api_create_multiple_flexible():
    """
    Endpoint untuk membuat multiple HUs dengan mode flexible
    """
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    logger.info("/hu/create-multiple-flexible dipanggil")

    # Gunakan fungsi create_multiple_hus yang sudah ada dengan modifikasi
    result, status_code = create_multiple_hus_flexible(data)
    return jsonify(result), status_code

def create_multiple_hus_flexible(data):
    """
    Modified version of create_multiple_hus untuk support semua mode
    """
    if 'hus' not in data or not isinstance(data['hus'], list):
        error_msg = "Data 'hus' harus berupa array"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    if len(data['hus']) == 0:
        error_msg = "Data 'hus' tidak boleh kosong"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    if 'sap_user' not in data or 'sap_password' not in data:
        error_msg = "SAP credentials (sap_user, sap_password) wajib diisi"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')
    creation_mode = data.get('creation_mode', 'split')  # Default to split mode

    default_user = os.getenv("SAP_USER", "auto_email")
    if sap_user == default_user:
        error_msg = f"SAP User tidak boleh menggunakan default/system user: {default_user}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 400

    logger.info(f"Create Multiple HUs Flexible - User: {sap_user}, Mode: {creation_mode}, Total: {len(data['hus'])}")

    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        error_msg = f"Gagal koneksi SAP: {conn_error}"
        logger.error(f"{error_msg}")
        return {"success": False, "error": error_msg}, 401

    try:
        results = []
        success_count = 0

        for i, hu_data in enumerate(data['hus']):
            hu_exid = hu_data.get('hu_exid', f'HU_{i+1}')
            logger.info(f"Memproses HU {i+1}/{len(data['hus'])}: {hu_exid}")

            try:
                required_fields = ['hu_exid', 'pack_mat', 'plant', 'stge_loc', 'material', 'pack_qty']
                missing_fields = [field for field in required_fields if field not in hu_data or not hu_data[field]]

                if missing_fields:
                    error_msg = f"HU {i+1}: Field wajib {', '.join(missing_fields)} tidak ditemukan"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                # Validasi quantity
                pack_qty = hu_data.get('pack_qty')
                if pack_qty is None or pack_qty == '':
                    error_msg = f"HU {i+1}: pack_qty tidak boleh kosong"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                try:
                    float(pack_qty)
                except (ValueError, TypeError):
                    error_msg = f"HU {i+1}: pack_qty harus berupa angka: {pack_qty}"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                cleaned_data = clean_hu_parameters(hu_data)

                # Prepare material
                material = cleaned_data['material']
                if material.isdigit() and len(material) < 18:
                    material = material.zfill(18)

                batch = cleaned_data.get('batch', '')
                sp_stck_no = cleaned_data.get('sp_stck_no', '')

                # Gunakan base_unit_qty jika valid, otherwise pack_qty
                quantity_to_use = cleaned_data.get('base_unit_qty')
                if quantity_to_use is None or quantity_to_use <= 0:
                    quantity_to_use = cleaned_data['pack_qty']

                if quantity_to_use <= 0:
                    error_msg = f"HU {i+1}: Quantity harus lebih besar dari 0, got: {quantity_to_use}"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                quantity_str = f"{quantity_to_use:.3f}".rstrip('0').rstrip('.') if '.' in f"{quantity_to_use}" else f"{quantity_to_use}"

                logger.info(f"HU {i+1} - Material: {material}, Qty: {quantity_str}, Mode: {creation_mode}")

                items = [{
                    "MATERIAL": material,
                    "PLANT": cleaned_data['plant'],
                    "STGE_LOC": cleaned_data['stge_loc'],
                    "PACK_QTY": quantity_str,
                    "HU_ITEM_TYPE": "1",
                    "BATCH": batch,
                    "SPEC_STOCK": "E" if sp_stck_no else "",
                    "SP_STCK_NO": sp_stck_no
                }]

                # Validasi final items
                pack_qty_val = items[0].get('PACK_QTY', '')
                if not pack_qty_val or pack_qty_val == '0':
                    error_msg = f"HU {i+1}: PACK_QTY tidak valid: '{pack_qty_val}'"
                    logger.error(f"{error_msg}")
                    results.append({
                        "hu_exid": hu_exid,
                        "success": False,
                        "error": error_msg
                    })
                    continue

                params = {
                    "I_HU_EXID": cleaned_data['hu_exid'],
                    "I_PACK_MAT": cleaned_data['pack_mat'],
                    "I_PLANT": cleaned_data['plant'],
                    "I_STGE_LOC": cleaned_data['stge_loc'],
                    "T_ITEMS": items
                }

                logger.info(f"Memanggil RFC untuk HU {i+1}: {cleaned_data['hu_exid']}")
                result = sap_conn.call('ZRFC_CREATE_HU_EXT', **params)

                success, message = validate_sap_response(result)

                if success:
                    hu_number = result.get('E_HUKEY', '')
                    hu_number_clean = hu_number.lstrip('0') or '0'

                    logger.info(f"HU {i+1} berhasil: {hu_number_clean}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": True,
                        "message": message,
                        "created_hu": hu_number_clean,
                        "material": material,
                        "quantity": quantity_to_use,
                        "mode": creation_mode
                    })
                    success_count += 1
                else:
                    logger.error(f"HU {i+1} gagal: {message}")
                    results.append({
                        "hu_exid": cleaned_data['hu_exid'],
                        "success": False,
                        "error": message,
                        "material": material,
                        "quantity": quantity_to_use,
                        "mode": creation_mode
                    })

            except ValueError as e:
                error_msg = f"Data tidak valid: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg
                })
            except Exception as e:
                error_msg = f"Error processing HU: {str(e)}"
                logger.error(f"HU {i+1} error: {error_msg}")
                logger.error(traceback.format_exc())
                results.append({
                    "hu_exid": hu_exid,
                    "success": False,
                    "error": error_msg
                })

        logger.info(f"Batch completed - Total: {len(results)}, Berhasil: {success_count}, Gagal: {len(results) - success_count}")

        summary = {
            "total": len(results),
            "success": success_count,
            "failed": len(results) - success_count,
            "creation_mode": creation_mode,
            "total_quantity": sum(float(r.get('quantity', 0)) for r in results if r.get('success'))
        }

        if success_count == 0:
            return {
                "success": False,
                "message": f"Semua {len(results)} HU gagal dibuat",
                "results": results,
                "summary": summary
            }, 400
        elif success_count == len(results):
            return {
                "success": True,
                "message": f"Semua {len(results)} HU berhasil dibuat",
                "results": results,
                "summary": summary
            }, 200
        else:
            return {
                "success": True,
                "message": f"Processed {len(results)} HUs, {success_count} successful, {len(results) - success_count} failed",
                "results": results,
                "summary": summary
            }, 207

    except Exception as e:
        error_msg = f"Error buat multiple HU flexible: {str(e)}"
        logger.error(f"{error_msg}")
        logger.error(traceback.format_exc())
        return {"success": False, "error": error_msg}, 500
    finally:
        if sap_conn:
            sap_conn.close()

@app.route('/stock/sync', methods=['POST'])
def api_stock_sync():
    logger.info("/stock/sync dipanggil")

    try:
        data = request.get_json() or {}
        plant = data.get('plant', '3000')
        storage_location = data.get('storage_location', '3D10')

        logger.info(f"Memulai sync stock data: Plant {plant}, Storage Location {storage_location}")

        success = sync_stock_data(plant, storage_location)

        if success:
            active_data = get_active_stock_data(plant, storage_location, include_inactive=False)

            return jsonify({
                "success": True,
                "message": "Sync stock data completed successfully",
                "data": {
                    "active_records_count": len(active_data) if active_data else 0,
                    "plant": plant,
                    "storage_location": storage_location
                }
            }), 200
        else:
            return jsonify({
                "success": False,
                "error": "Sync stock data failed"
            }), 500

    except Exception as e:
        error_msg = f"Error dalam sync stock data: {str(e)}"
        logger.error(f"{error_msg}")
        return jsonify({
            "success": False,
            "error": error_msg
        }), 500

@app.route('/stock/view', methods=['GET'])
def api_stock_view():
    try:
        plant = request.args.get('plant', '3000')
        storage_location = request.args.get('storage_location', '3D10')
        include_inactive = request.args.get('include_inactive', 'false').lower() == 'true'

        stock_data = get_active_stock_data(plant, storage_location, include_inactive)

        if stock_data is None:
            return jsonify({
                "success": False,
                "error": "Gagal mengambil data stock"
            }), 500

        return jsonify({
            "success": True,
            "data": stock_data,
            "summary": {
                "plant": plant,
                "storage_location": storage_location,
                "total_records": len(stock_data),
                "include_inactive": include_inactive
            }
        }), 200

    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500

@app.route('/stock/view-all', methods=['GET'])
def api_stock_view_all():
    try:
        plant = request.args.get('plant', '3000')
        storage_location = request.args.get('storage_location', '3D10')

        stock_data = get_active_stock_data(plant, storage_location, include_inactive=True)

        if stock_data is None:
            return jsonify({
                "success": False,
                "error": "Gagal mengambil data stock"
            }), 500

        active_count = sum(1 for item in stock_data if item.get('is_active') == 1 and item.get('stock_quantity', 0) > 0)
        inactive_count = len(stock_data) - active_count

        return jsonify({
            "success": True,
            "data": stock_data,
            "summary": {
                "plant": plant,
                "storage_location": storage_location,
                "total_records": len(stock_data),
                "active_records": active_count,
                "inactive_records": inactive_count
            }
        }), 200

    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500

@app.route('/stock/status', methods=['GET'])
def api_stock_status():
    try:
        plant = request.args.get('plant', '3000')
        storage_location = request.args.get('storage_location', '3D10')

        mysql_conn = connect_mysql()
        if not mysql_conn:
            return jsonify({"success": False, "error": "Database connection failed"}), 500

        with mysql_conn.cursor() as cursor:
            cursor.execute("""
                SELECT
                    COUNT(*) as total_active,
                    SUM(stock_quantity) as total_stock_quantity,
                    COUNT(DISTINCT material) as unique_materials,
                    MAX(last_updated) as last_updated_time
                FROM stock_data
                WHERE plant = %s
                AND storage_location = %s
                AND is_active = 1
                AND stock_quantity > 0
            """, (plant, storage_location))

            active_result = cursor.fetchone()

            cursor.execute("""
                SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN is_active = 1 AND stock_quantity > 0 THEN 1 ELSE 0 END) as active_with_stock,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_records
                FROM stock_data
                WHERE plant = %s AND storage_location = %s
            """, (plant, storage_location))

            stats_result = cursor.fetchone()

            return jsonify({
                "success": True,
                "data": {
                    "plant": plant,
                    "storage_location": storage_location,
                    "view_data": {
                        "active_records": active_result[0],
                        "total_stock_quantity": float(active_result[1]) if active_result[1] else 0,
                        "unique_materials": active_result[2],
                        "last_updated": active_result[3].isoformat() if active_result[3] else None
                    },
                    "system_stats": {
                        "total_records": stats_result[0],
                        "active_with_stock": stats_result[1],
                        "inactive_records": stats_result[2]
                    }
                }
            }), 200

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500
    finally:
        if mysql_conn:
            mysql_conn.close()

@app.route('/sync/status', methods=['GET'])
def api_sync_status():
    return jsonify({
        "success": True,
        "data": last_sync_status
    }), 200

@app.route('/sync/now', methods=['POST'])
def api_sync_now():
    try:
        success = sync_stock_data('3000', '3D10')
        if success:
            return jsonify({"success": True}), 200
        else:
            return jsonify({"success": False, "error": "Sync failed"}), 500
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500

@app.route('/hu/debug-response', methods=['POST'])
def debug_hu_response():
    """Endpoint untuk debug response SAP"""
    data = request.get_json()
    if not data:
        return jsonify({"success": False, "error": "Data kosong"}), 400

    sap_user = data.get('sap_user')
    sap_password = data.get('sap_password')

    if not sap_user or not sap_password:
        return jsonify({"success": False, "error": "SAP credentials required"}), 400

    sap_conn, conn_error = connect_sap_with_credentials(sap_user, sap_password)
    if not sap_conn:
        return jsonify({"success": False, "error": conn_error}), 401

    try:
        # Test dengan data minimal
        test_params = {
            "I_HU_EXID": "9900000001",
            "I_PACK_MAT": "50016873",
            "I_PLANT": "3000",
            "I_STGE_LOC": "3D10",
            "T_ITEMS": [{
                "MATERIAL": "000000000030002555",
                "PLANT": "3000",
                "STGE_LOC": "3D10",
                "PACK_QTY": "1",
                "HU_ITEM_TYPE": "1",
                "BATCH": "",
                "SPEC_STOCK": "",
                "SP_STCK_NO": ""
            }]
        }

        logger.info("Debug: Calling SAP RFC...")
        result = sap_conn.call('ZRFC_CREATE_HU_EXT', **test_params)

        return jsonify({
            "success": True,
            "sap_response": result,
            "analysis": {
                "has_e_hukey": 'E_HUKEY' in result,
                "e_hukey_value": result.get('E_HUKEY'),
                "has_e_return": 'E_RETURN' in result,
                "e_return_value": result.get('E_RETURN'),
                "has_t_items": 'T_ITEMS' in result,
                "t_items_count": len(result.get('T_ITEMS', [])),
                "all_keys": list(result.keys())
            }
        })

    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500
    finally:
        if sap_conn:
            sap_conn.close()

@app.route('/health', methods=['GET'])
def api_health():
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
    logger.info("Starting SAP HU Automation API")

    ensure_magry_column_exists()
    ensure_advanced_columns_exist()

    if connect_mysql():
        logger.info("Database siap")
    else:
        logger.error("Database error")

    logger.info("Testing koneksi SAP...")
    sap_conn = connect_sap()
    if sap_conn:
        logger.info("SAP siap")
        sap_conn.close()
    else:
        logger.warning("SAP tidak tersedia")

    start_scheduler()

    logger.info("Starting Flask server di port 5000...")
    try:
        app.run(host='0.0.0.0', port=5000, debug=False)
    except Exception as e:
        logger.error(f"Gagal start Flask server: {e}")
    finally:
        stop_scheduler()
