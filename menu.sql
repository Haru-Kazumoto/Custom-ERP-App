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