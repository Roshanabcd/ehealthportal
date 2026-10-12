-- ============================================================
--  E-Health Government Service Portal  -  Database Script
--  Import this file in phpMyAdmin (Import tab) or run:
--      /opt/lampp/bin/mysql -u root < database/ehealth_db.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS ehealth_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ehealth_portal;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs, notifications, health_records, appointments, doctors, health_services, users;
SET FOREIGN_KEY_CHECKS = 1;

-- USER (ER diagram: user_id, name, email, password, role, phone, created_at)
CREATE TABLE users (
  user_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','doctor','citizen') NOT NULL DEFAULT 'citizen',
  phone       VARCHAR(20)  DEFAULT NULL,
  gender      ENUM('male','female','other') DEFAULT NULL,
  dob         DATE DEFAULT NULL,
  address     VARCHAR(200) DEFAULT NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until  DATETIME DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- HEALTH_SERVICE
CREATE TABLE health_services (
  service_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(120) NOT NULL,
  description  TEXT,
  department   VARCHAR(80) NOT NULL,
  status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- DOCTOR (user_id links a doctor to a login account)
CREATE TABLE doctors (
  doctor_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED DEFAULT NULL UNIQUE,
  name           VARCHAR(100) NOT NULL,
  specialization VARCHAR(100) NOT NULL,
  department     VARCHAR(80)  NOT NULL,
  contact        VARCHAR(20)  DEFAULT NULL,
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doc_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- APPOINTMENT (also acts as the "service request" with trackable status)
CREATE TABLE appointments (
  appointment_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED NOT NULL,
  service_id       INT UNSIGNED NOT NULL,
  doctor_id        INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  reason           VARCHAR(500) DEFAULT NULL,
  status           ENUM('pending','approved','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  admin_note       VARCHAR(300) DEFAULT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_slot (doctor_id, appointment_date, appointment_time),
  CONSTRAINT fk_app_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)               ON DELETE CASCADE,
  CONSTRAINT fk_app_service FOREIGN KEY (service_id) REFERENCES health_services(service_id)  ON DELETE CASCADE,
  CONSTRAINT fk_app_doctor  FOREIGN KEY (doctor_id)  REFERENCES doctors(doctor_id)           ON DELETE CASCADE
) ENGINE=InnoDB;

-- HEALTH_RECORD
CREATE TABLE health_records (
  record_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NOT NULL,
  doctor_id      INT UNSIGNED NOT NULL,
  appointment_id INT UNSIGNED DEFAULT NULL,
  diagnosis      VARCHAR(500) NOT NULL,
  prescription   TEXT,
  record_date    DATE NOT NULL,
  notes          TEXT,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rec_user   FOREIGN KEY (user_id)        REFERENCES users(user_id)               ON DELETE CASCADE,
  CONSTRAINT fk_rec_doctor FOREIGN KEY (doctor_id)      REFERENCES doctors(doctor_id)           ON DELETE CASCADE,
  CONSTRAINT fk_rec_app    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Notifications (synopsis: "receive service-related notifications")
CREATE TABLE notifications (
  notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(150) NOT NULL,
  message    VARCHAR(500) NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_not_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activity log (admin monitoring / traceability)
CREATE TABLE activity_logs (
  log_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED DEFAULT NULL,
  action     VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--  SAMPLE DATA
--  Admin   : admin@ehealth.gov.np   / Admin@123
--  Doctors : doctor1@ehealth.gov.np ... doctor6@...  / Doctor@123
--  Citizens: citizen1@example.com ... citizen3@...   / Citizen@123
-- ============================================================
INSERT INTO users (user_id,name,email,password,role,phone,gender,dob,address) VALUES
(1,'System Administrator','admin@ehealth.gov.np','$2y$10$ysrab5.pcrDh99MbmrI8zuyEXbRTR9gt8GB/nqqUkeaOQgEoPBeWW','admin','9800000000','other',NULL,'Kathmandu'),
(2,'Dr. Anita Sharma','doctor1@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111101','female','1985-03-12','Kathmandu'),
(3,'Dr. Bikash Thapa','doctor2@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111102','male','1980-07-21','Lalitpur'),
(4,'Dr. Sunita Rai','doctor3@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111103','female','1988-11-02','Bhaktapur'),
(5,'Dr. Prakash Adhikari','doctor4@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111104','male','1978-01-30','Kathmandu'),
(6,'Dr. Meera Gurung','doctor5@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111105','female','1990-05-18','Pokhara'),
(7,'Dr. Rajesh Karki','doctor6@ehealth.gov.np','$2y$10$R22VONdd.wiP5CHWwJUhke9qfJ10mWJNSQMmViHmc/rGLsYUVNChe','doctor','9811111106','male','1983-09-09','Kathmandu'),
(8,'Sita Poudel','citizen1@example.com','$2y$10$pi/rTadVy0oyKJergfX74Oh4wfN01jXx5THIMJEhO8dgHcCK49aIa','citizen','9841000001','female','1995-04-15','Baneshwor, Kathmandu'),
(9,'Ram Bahadur Tamang','citizen2@example.com','$2y$10$pi/rTadVy0oyKJergfX74Oh4wfN01jXx5THIMJEhO8dgHcCK49aIa','citizen','9841000002','male','1971-12-01','Kirtipur, Kathmandu'),
(10,'Gita Shrestha','citizen3@example.com','$2y$10$pi/rTadVy0oyKJergfX74Oh4wfN01jXx5THIMJEhO8dgHcCK49aIa','citizen','9841000003','female','2001-08-23','Patan, Lalitpur');

INSERT INTO health_services (service_id,service_name,description,department) VALUES
(1,'General OPD Consultation','Outpatient consultation for common illnesses, fever, infections and routine health problems.','General Medicine'),
(2,'Maternal & Child Health','Antenatal check-ups, postnatal care, growth monitoring and advice for mothers and infants.','Gynecology & Pediatrics'),
(3,'Immunization Service','Vaccination for children and adults according to the national immunization schedule.','Pediatrics'),
(4,'Dental Care','Dental check-up, cleaning, extraction and oral health counselling.','Dental'),
(5,'Eye Care & Vision Screening','Eye examination, vision screening, cataract assessment and spectacle prescription.','Ophthalmology'),
(6,'TB & Chronic Disease Clinic','Diagnosis, DOTS treatment follow-up and management of diabetes and hypertension.','General Medicine'),
(7,'Mental Health Counselling','Confidential psychosocial counselling and psychiatric consultation.','Psychiatry'),
(8,'Skin & Allergy Clinic','Diagnosis and treatment of skin diseases and allergies.','Dermatology');

INSERT INTO doctors (doctor_id,user_id,name,specialization,department,contact) VALUES
(1,2,'Dr. Anita Sharma','General Physician','General Medicine','9811111101'),
(2,3,'Dr. Bikash Thapa','Pediatrician','Pediatrics','9811111102'),
(3,4,'Dr. Sunita Rai','Gynecologist','Gynecology & Pediatrics','9811111103'),
(4,5,'Dr. Prakash Adhikari','Dentist','Dental','9811111104'),
(5,6,'Dr. Meera Gurung','Ophthalmologist','Ophthalmology','9811111105'),
(6,7,'Dr. Rajesh Karki','Psychiatrist','Psychiatry','9811111106');

INSERT INTO appointments (appointment_id,user_id,service_id,doctor_id,appointment_date,appointment_time,reason,status) VALUES
(1,8,1,1,DATE_SUB(CURDATE(),INTERVAL 10 DAY),'10:00:00','Fever and sore throat for three days','completed'),
(2,9,6,1,DATE_SUB(CURDATE(),INTERVAL 5 DAY),'11:30:00','Blood pressure follow-up','completed'),
(3,8,2,3,DATE_ADD(CURDATE(),INTERVAL 3 DAY),'09:30:00','Routine antenatal check-up','approved'),
(4,10,5,5,DATE_ADD(CURDATE(),INTERVAL 4 DAY),'14:00:00','Blurred vision while reading','pending'),
(5,9,4,4,DATE_ADD(CURDATE(),INTERVAL 5 DAY),'12:00:00','Tooth pain','pending');

INSERT INTO health_records (user_id,doctor_id,appointment_id,diagnosis,prescription,record_date,notes) VALUES
(8,1,1,'Acute pharyngitis','Paracetamol 500mg twice daily for 3 days; warm saline gargles; plenty of fluids.',DATE_SUB(CURDATE(),INTERVAL 10 DAY),'Advised rest. Return if fever persists beyond 3 days.'),
(9,1,2,'Hypertension (stage 1) - follow-up','Amlodipine 5mg once daily in the morning.',DATE_SUB(CURDATE(),INTERVAL 5 DAY),'BP 142/92. Reduce salt intake, daily walk 30 minutes. Review in one month.');

INSERT INTO notifications (user_id,title,message) VALUES
(8,'Appointment approved','Your appointment with Dr. Sunita Rai has been approved.'),
(10,'Appointment received','Your appointment request has been received and is awaiting approval.'),
(1,'Welcome','Welcome to the E-Health Government Service Portal admin panel.');

-- Emergencies (Requests for immediate assistance)
CREATE TABLE emergencies (
  emergency_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED DEFAULT NULL,
  requester_name VARCHAR(100) NOT NULL,
  contact_number VARCHAR(20) NOT NULL,
  emergency_type VARCHAR(50) NOT NULL,
  location VARCHAR(255) NOT NULL,
  description TEXT,
  status ENUM('pending', 'dispatched', 'resolved', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_emerg_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
