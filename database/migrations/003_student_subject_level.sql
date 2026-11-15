ALTER TABLE registrations ADD COLUMN subject VARCHAR(30) NULL AFTER phone, ADD COLUMN student_level VARCHAR(100) NULL AFTER subject;
