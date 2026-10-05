INSERT INTO menus
(name, `key`, icon, url, description, is_active, parent_id, created_at, updated_at)
VALUES
('Dashboard', 'dashboard', 'LayoutDashboard', '/dashboard', 'Main menu of user', 1, NULL, NOW(), NOW()),
('Purchase Order', 'purchase_order', 'FileBox', '/purchase-orders', 'Menu of purchase order', 1, NULL, NOW(), NOW()),
('Daftar Dokumen', 'list_po_documents', 'FileText', '/purchase-orders/documents', 'Menu of list purchase order', 1, 2, NOW(), NOW()),
('Revisi PO', 'revision_po_documents', 'FileXCorner', '/purchase-orders/revisions', 'Menu of revision purchase order', 1, 2, NOW(), NOW()),
('Sub Sales Order', 'sub_sales_order', 'ScrollText', '/sub-sales-orders', 'Menu of sub sales order after purchase order document done', 1, NULL, NOW(), NOW()),
('Surat Jalan', 'travel_documents', 'FileCheck', '/travel-documents', 'Menu of travel documents after delivery order document done', 1, NULL, NOW(), NOW()),
('Ekspedisi', 'expedition', 'Truck', '/expeditions', 'Menu of expedition', 1, NULL, NOW(), NOW()),
('Stok Gudang', 'warehouse_stock_products', 'Package', '/warehouse-stocks', 'Menu of warehouse stock', 1, NULL, NOW(), NOW());

-- Pencocokan menu aktif sidebar pakai NAMA ROUTE, bukan kesamaan URL, supaya
-- halaman turunan (create/show) tetap menyalakan menu induknya walau URL-nya saudara.
-- Kolom `route_name` + `active_routes` dibuat oleh migration
-- 2026_10_02_070900_add_route_columns_to_menus_table.php
UPDATE menus SET route_name = 'dashboard', active_routes = NULL WHERE `key` = 'dashboard';

-- Menu grup struktural, url `/purchase-orders` tidak pernah jadi route.
UPDATE menus SET route_name = NULL, active_routes = NULL WHERE `key` = 'purchase_order';

-- create/show adalah turunan dari daftar dokumen, jadi tetap menyalakan menu ini.
UPDATE menus
SET route_name = 'purchase-order.index',
    active_routes = JSON_ARRAY('purchase-order.create', 'purchase-order.show')
WHERE `key` = 'list_po_documents';

-- Form revisi adalah turunan dari daftar revisi, jadi tetap menyalakan menu ini.
UPDATE menus
SET route_name = 'purchase-order.revisions',
    active_routes = JSON_ARRAY('purchase-order.revise')
WHERE `key` = 'revision_po_documents';

UPDATE menus
SET route_name = 'sub-sales-order.index',
    active_routes = JSON_ARRAY('sub-sales-order.create')
WHERE `key` = 'sub_sales_order';

-- Route-nya belum terdaftar, biarkan kosong sampai modulnya dibuat.
UPDATE menus SET route_name = NULL, active_routes = NULL WHERE `key` IN ('travel_documents', 'expedition', 'warehouse_stock_products');