/* =========================================================
   CREAZIONE DATABASE
   ========================================================= */
CREATE DATABASE IF NOT EXISTS FornitoriPezziDB
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE FornitoriPezziDB;

-- =========================================================
-- RESET TABELLE
-- =========================================================
DROP TABLE IF EXISTS Catalogo;
DROP TABLE IF EXISTS Pezzi;
DROP TABLE IF EXISTS Fornitori;
DROP TABLE IF EXISTS Users;

-- =========================================================
-- TABELLE
-- =========================================================

-- Tabella Users per autenticazione
CREATE TABLE Users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL, -- hash bcrypt
  email      VARCHAR(100) NOT NULL UNIQUE,
  role       ENUM('admin', 'fornitore') NOT NULL DEFAULT 'fornitore',
  active     BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Fornitori (
  fid        VARCHAR(50) PRIMARY KEY,
  fnome      VARCHAR(100) NOT NULL,
  indirizzo  VARCHAR(200),
  user_id    INT NULL, -- NULL = fornitore non registrato, gestito solo da admin
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Pezzi (
  pid        VARCHAR(50) PRIMARY KEY,
  pnome      VARCHAR(100) NOT NULL,
  colore     VARCHAR(30)  NOT NULL,
  descrizione TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_pnome (pnome),
  INDEX idx_colore (colore)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Catalogo (
  fid        VARCHAR(50) NOT NULL,
  pid        VARCHAR(50) NOT NULL,
  costo      DECIMAL(10,2) NOT NULL CHECK (costo > 0),
  quantita   INT NOT NULL DEFAULT 0,
  note       TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (fid, pid),
  FOREIGN KEY (fid) REFERENCES Fornitori(fid) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (pid) REFERENCES Pezzi(pid) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_costo (costo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- USERS (password: "password123" per tutti, hash bcrypt)
-- =========================================================
INSERT INTO Users (username, password, email, role, active) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fornitoridb.com', 'admin', TRUE),
('acme_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acme@example.com', 'fornitore', TRUE),
('widget_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'widget@example.com', 'fornitore', TRUE),
('supplies_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'supplies@example.com', 'fornitore', TRUE);

-- =========================================================
-- FORNITORI
-- =========================================================
INSERT INTO Fornitori (fid, fnome, indirizzo, user_id) VALUES
('F01', 'Acme',          'Via Roma 1, Milano', 2),       -- associato a acme_user
('F02', 'WidgetCorp',    'Via Milano 2, Torino', 3),     -- associato a widget_user
('F03', 'Supplies Inc',  'Via Torino 3, Genova', 4),     -- associato a supplies_user
('F04', 'TechParts',     'Via Venezia 4, Venezia', NULL), -- non registrato
('F05', 'MegaSupplies',  'Via Napoli 5, Napoli', NULL),  -- non registrato
('F06', 'GreenTech',     'Via Palermo 6, Palermo', NULL),-- non registrato
('F07', 'RedComponents', 'Via Firenze 7, Firenze', NULL);-- non registrato

-- =========================================================
-- PEZZI
-- =========================================================
INSERT INTO Pezzi (pid, pnome, colore, descrizione) VALUES
('P01', 'Bullone',        'rosso', 'Bullone M8x20mm in acciaio'),
('P02', 'Vite',           'blu', 'Vite autofilettante 4x30mm'),
('P03', 'Dado',           'rosso', 'Dado esagonale M8'),
('P04', 'Rivetto',        'verde', 'Rivetto in alluminio 4x8mm'),
('P05', 'Molla',          'blu', 'Molla a compressione'),
('P06', 'Guarnizione',    'rosso', 'Guarnizione in gomma Ø30mm'),
('P07', 'Cuscinetto',     'verde', 'Cuscinetto a sfere 608ZZ'),
('P08', 'Cavo',           'blu', 'Cavo elettrico 1.5mm²'),
('P09', 'Resistore',      'rosso', 'Resistore 1kΩ 1/4W'),
('P10', 'Condensatore',   'verde', 'Condensatore elettrolitico 100µF'),
('P11', 'Trasformatore',  'verde', 'Trasformatore 220V/12V'),
('P12', 'Fusibile',       'rosso', 'Fusibile 5A ritardato');

-- =========================================================
-- CATALOGO (fid, pid, costo, quantita, note)
-- =========================================================
INSERT INTO Catalogo (fid, pid, costo, quantita, note) VALUES
-- Acme
('F01','P01',10.5,  100, 'Disponibile in magazzino'),
('F01','P02',5.1,   250, NULL),
('F01','P03',8.4,   150, 'Pronta consegna'),
('F01','P04',6.0,   80,  NULL),
('F01','P05',7.3,   200, NULL),
('F01','P06',9.0,   120, 'Alta qualità'),
('F01','P07',12.2,  50,  NULL),
('F01','P08',4.6,   300, 'Offerta speciale'),
('F01','P09',15.0,  400, NULL),
('F01','P10',8.6,   180, NULL),
('F01','P11',18.0,  30,  'Su ordinazione'),
('F01','P12',6.5,   220, NULL),

-- WidgetCorp
('F02','P01',11.0,  90,  NULL),
('F02','P02',5.0,   200, 'Stock limitato'),
('F02','P03',8.1,   140, NULL),
('F02','P04',6.7,   75,  NULL),
('F02','P05',7.0,   180, NULL),
('F02','P07',11.8,  60,  'Consegna in 7 giorni'),

-- Supplies Inc
('F03','P08',3.9,   500, 'Miglior prezzo'),
('F03','P09',14.8,  350, NULL),
('F03','P10',9.8,   160, NULL),
('F03','P11',17.5,  40,  NULL),
('F03','P12',6.2,   200, 'Certificato CE'),

-- TechParts
('F04','P04',6.1,   65,  NULL),
('F04','P07',12.0,  55,  'Alta precisione'),
('F04','P10',8.9,   170, NULL),
('F04','P11',18.3,  25,  'Import diretto'),

-- MegaSupplies
('F05','P02',5.3,   230, NULL),
('F05','P05',7.4,   190, NULL),
('F05','P06',9.2,   110, 'Garanzia 2 anni'),
('F05','P08',4.8,   280, NULL),

-- GreenTech
('F06','P04',6.4,   70,  'Eco-friendly'),
('F06','P07',12.5,  45,  NULL),
('F06','P10',9.1,   175, 'Componenti green'),

-- RedComponents
('F07','P01',10.9,  95,  NULL),
('F07','P03',8.3,   145, 'Qualità premium'),
('F07','P06',9.1,   115, NULL),
('F07','P09',15.2,  380, 'Testato'),
('F07','P12',6.7,   210, NULL);