USE maruba;

-- Update roles with detailed permissions (JSON)
UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "users": ["view","create","edit","delete"],
  "roles": ["view","create","edit","delete"],
  "members": ["view","create","edit","delete"],
  "products": ["view","create","edit","delete"],
  "loans": ["view","create","edit","delete","approve","disburse"],
  "surveys": ["view","create","edit","delete"],
  "repayments": ["view","create","edit","delete"],
  "loan_docs": ["view","create","delete"],
  "audit_logs": ["view"],
  "reports": ["view","export"],
  "documents": ["view","download"]
}' WHERE name = 'admin';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "cash": ["view","create","edit"],
  "transactions": ["view","create","edit"],
  "repayments": ["view",
  "documents": ["view","download"],"create","edit"],
  "loan_docs": ["view"]
}' WHERE name = 'kasir';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "savings": ["view","create","edit"],
  "transactions": ["view","create","edit"],
  "members": ["view"]
}' WHERE name = 'teller';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "surveys": ["view","create","edit"],
  "loan_docs": ["view","create","delete"],
  "members": ["view"]
}' WHERE name = 'staf_lapangan';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "loans": ["view","approve","override"],
  "products": ["view","edit"],,
  "documents": ["view","download"]
  "reports": ["view","export"]
}' WHERE name = 'manajer';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "transactions": ["view","reconcile"],
  "reports": ["view","export"],
  "audit_logs": ["view"]
}' WHERE name = 'akuntansi';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "surveys": ["view","create","edit"],
  "loan_docs": ["view","create","delete"],
  "members": ["view"]
}' WHERE name = 'surveyor';

UPDATE roles SET permissions = '{
  "dashboard": ["view"],
  "repayments": ["view","create","edit"],
  "loan_docs": ["view","create","delete"],
  "members": ["view"]
}' WHERE name = 'collector';

-- Additional mock data
INSERT INTO members (name, nik, phone, address, lat, lng) VALUES
 ('Rina Siregar', '1204050101010003', '081234567892', 'Pangururan', -2.6510000, 99.0510000),
 ('Budi Nainggolan', '1204050101010004', '081234567893', 'Simanindo', -2.6810000, 99.0710000),
 ('Anto Sihombing', '1204050101010005', '081234567894', 'Onan Runggu', -2.6700000, 99.0600000);

INSERT INTO products (name, type, rate, tenor_months, fee) VALUES
 ('Simpanan Pokok', 'savings', 0, 0, 0),
 ('Simpanan Wajib', 'savings', 0, 0, 0),
 ('Simpanan Sukarela', 'savings', 0.50, 0, 0),
 ('Pinjaman Konsumtif', 'loan', 2.00, 12, 100000),
 ('Pinjaman Produktif', 'loan', 1.75, 24, 150000);

INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status, assigned_surveyor_id, assigned_collector_id, approved_by, disbursed_by)
VALUES
 (2, 4, 8000000, 12, 2.00, 'approved', 4, 5, 1, 1),
 (3, 5, 12000000, 24, 1.75, 'survey', 4, 5, NULL, NULL),
 (4, 4, 6000000, 12, 2.00, 'disbursed', 4, 5, 1, 1);

INSERT INTO surveys (loan_id, surveyor_id, result, score, geo_lat, geo_lng) VALUES
 (2, 4, 'Usaha toko kelontong stabil, lokasi strategis', 85, -2.6811000, 99.0712000),
 (3, 4, 'Usaha bengkel, pendapatan fluktuatif', 70, -2.6701000, 99.0602000),
 (4, 4, 'Usaha warung makan, ramai', 90, -2.6511000, 99.0512000);

INSERT INTO repayments (loan_id, due_date, amount_due, amount_paid, method, status, collector_id) VALUES
 (2, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 800000, 0, NULL, 'due', 5),
 (2, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 800000, 0, NULL, 'due', 5),
 (4, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 600000, 600000, 'tunai', 'paid', 5),
 (4, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 600000, 0, NULL, 'due', 5);

INSERT INTO loan_docs (loan_id, doc_type, path, uploaded_by) VALUES
 (2, 'ktp', '/uploads/ktp_rina.jpg', 4),
 (2, 'kk', '/uploads/kk_rina.jpg', 4),
 (2, 'slip_gaji', '/uploads/slip_rina.jpg', 4),
 (3, 'ktp', '/uploads/ktp_budi.jpg', 4),
 (3, 'kk', '/uploads/kk_budi.jpg', 4),
 (3, 'bukti_usaha', '/uploads/usaha_budi.jpg', 4),
 (4, 'ktp', '/uploads/ktp_anto.jpg', 4),
 (4, 'kk', '/uploads/kk_anto.jpg', 4),
 (4, 'surat_kerja', '/uploads/kerja_anto.jpg', 4);

INSERT INTO audit_logs (user_id, action, entity, entity_id, meta) VALUES
 (1, 'login', 'user', 1, '{"ip":"127.0.0.1"}'),
 (1, 'create', 'loan', 2, '{"member_id":2,"amount":8000000}'),
 (1, 'approve', 'loan', 2, '{"approved_by":1}'),
 (1, 'disburse', 'loan', 2, '{"disbursed_by":1}'),
 (4, 'create', 'survey', 2, '{"loan_id":2,"score":85}'),
 (5, 'create', 'repayment', 4, '{"loan_id":4,"amount":600000}');
