-- PostgreSQL: merge the HCODE staging table into the application table.
-- This statement is safe to run repeatedly. Existing latitude/longitude,
-- phone, website and opening-hours values are intentionally preserved.

WITH cleaned AS (
    SELECT
        NULLIF(BTRIM(REGEXP_REPLACE(moph_code_9_new, '[="]', '', 'g')), '') AS code_9_new,
        NULLIF(BTRIM(REGEXP_REPLACE(moph_code_9, '[="]', '', 'g')), '') AS code_9,
        NULLIF(BTRIM(REGEXP_REPLACE(moph_code_5, '[="]', '', 'g')), '') AS code_5,
        NULLIF(BTRIM(REGEXP_REPLACE(license_code_11, '[="]', '', 'g')), '') AS licence_11,
        health_office_import.*
    FROM health_office_import
), prepared AS (
    SELECT
        'MOPH-' || COALESCE(code_9_new, code_9, code_5) AS target_facility_id,
        cleaned.*,
        ROW_NUMBER() OVER (
            PARTITION BY BTRIM(facility_name), BTRIM(province), BTRIM(district)
            ORDER BY code_9_new NULLS LAST, code_9 NULLS LAST, code_5 NULLS LAST
        ) AS name_location_rank,
        CASE
            WHEN service_type = 'โรงพยาบาลส่งเสริมสุขภาพตำบล' THEN 'C'
            WHEN service_type LIKE '%คลินิก%' THEN 'C'
            WHEN service_type LIKE 'โรงพยาบาล%' THEN 'H'
            ELSE 'C'
        END AS target_facility_type,
        CASE WHEN source_status = 'กำลังใช้งาน' THEN '1' ELSE '2' END AS target_status
    FROM cleaned
    WHERE COALESCE(code_9_new, code_9, code_5) IS NOT NULL
      AND NULLIF(BTRIM(facility_name), '') IS NOT NULL
)
INSERT INTO healthcare_facilities (
    facility_id,
    facility_name,
    facility_type,
    moph_code_9_new,
    moph_code_9,
    moph_code_5,
    license_code_11,
    organization_type,
    service_type,
    affiliation,
    department,
    hospital_level,
    network_type,
    actual_beds,
    source_status,
    service_area,
    address,
    province_code,
    province,
    district_code,
    district,
    sub_district_code,
    sub_district,
    village,
    postal_code,
    parent_facility,
    established_date,
    closed_date,
    source_updated_at,
    data_source,
    status,
    created_at,
    updated_at
)
SELECT
    LEFT(target_facility_id, 20),
    LEFT(BTRIM(facility_name), 255),
    target_facility_type,
    LEFT(code_9_new, 9),
    LEFT(code_9, 9),
    LEFT(code_5, 5),
    LEFT(licence_11, 11),
    LEFT(NULLIF(BTRIM(organization_type), ''), 150),
    LEFT(NULLIF(BTRIM(service_type), ''), 150),
    LEFT(NULLIF(BTRIM(affiliation), ''), 150),
    LEFT(NULLIF(BTRIM(department), ''), 150),
    LEFT(NULLIF(BTRIM(hospital_level), ''), 100),
    LEFT(NULLIF(BTRIM(network_type), ''), 100),
    CASE
        WHEN actual_beds ~ '^\s*[0-9]+\s*$' THEN BTRIM(actual_beds)::INTEGER
        ELSE NULL
    END,
    LEFT(NULLIF(BTRIM(source_status), ''), 100),
    LEFT(NULLIF(BTRIM(service_area), ''), 20),
    NULLIF(BTRIM(address), ''),
    LEFT(NULLIF(BTRIM(province_code), ''), 2),
    LEFT(NULLIF(BTRIM(province), ''), 100),
    LEFT(NULLIF(BTRIM(district_code), ''), 4),
    LEFT(NULLIF(BTRIM(district), ''), 100),
    LEFT(NULLIF(BTRIM(sub_district_code), ''), 6),
    LEFT(NULLIF(BTRIM(sub_district), ''), 100),
    LEFT(NULLIF(BTRIM(village), ''), 20),
    LEFT(NULLIF(BTRIM(postal_code), ''), 10),
    LEFT(NULLIF(BTRIM(parent_facility), ''), 255),
    CASE
        WHEN established_date ~ '^\d{2}/\d{2}/\d{4}'
            THEN TO_DATE(LEFT(established_date, 10), 'DD/MM/YYYY')
        ELSE NULL
    END,
    CASE
        WHEN closed_date ~ '^\d{2}/\d{2}/\d{4}'
            THEN TO_DATE(LEFT(closed_date, 10), 'DD/MM/YYYY')
        ELSE NULL
    END,
    CASE
        WHEN source_updated_at ~ '^\d{2}/\d{2}/\d{4} \d{2}:\d{2}:\d{2}$'
            THEN TO_TIMESTAMP(source_updated_at, 'DD/MM/YYYY HH24:MI:SS')
        ELSE NULL
    END,
    'moph-hcode',
    target_status,
    NOW(),
    NOW()
FROM prepared
WHERE name_location_rank = 1
ON CONFLICT (facility_id) DO UPDATE SET
    facility_name = EXCLUDED.facility_name,
    facility_type = EXCLUDED.facility_type,
    moph_code_9_new = EXCLUDED.moph_code_9_new,
    moph_code_9 = EXCLUDED.moph_code_9,
    moph_code_5 = EXCLUDED.moph_code_5,
    license_code_11 = EXCLUDED.license_code_11,
    organization_type = EXCLUDED.organization_type,
    service_type = EXCLUDED.service_type,
    affiliation = EXCLUDED.affiliation,
    department = EXCLUDED.department,
    hospital_level = EXCLUDED.hospital_level,
    network_type = EXCLUDED.network_type,
    actual_beds = EXCLUDED.actual_beds,
    source_status = EXCLUDED.source_status,
    service_area = EXCLUDED.service_area,
    address = EXCLUDED.address,
    province_code = EXCLUDED.province_code,
    province = EXCLUDED.province,
    district_code = EXCLUDED.district_code,
    district = EXCLUDED.district,
    sub_district_code = EXCLUDED.sub_district_code,
    sub_district = EXCLUDED.sub_district,
    village = EXCLUDED.village,
    postal_code = EXCLUDED.postal_code,
    parent_facility = EXCLUDED.parent_facility,
    established_date = EXCLUDED.established_date,
    closed_date = EXCLUDED.closed_date,
    source_updated_at = EXCLUDED.source_updated_at,
    data_source = EXCLUDED.data_source,
    status = EXCLUDED.status,
    updated_at = NOW();
