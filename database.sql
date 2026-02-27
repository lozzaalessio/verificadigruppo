/* =========================================================
   RESET
   ========================================================= */
DROP TABLE IF EXISTS Catalogo;
DROP TABLE IF EXISTS Pezzi;
DROP TABLE IF EXISTS Fornitori;

/* =========================================================
   TABELLE
   ========================================================= */
CREATE TABLE Fornitori (
  fid       VARCHAR(50) PRIMARY KEY,
  fnome     VARCHAR(100) NOT NULL,
  indirizzo VARCHAR(200)
);

CREATE TABLE Pezzi (
  pid    VARCHAR(50) PRIMARY KEY,
  pnome  VARCHAR(100) NOT NULL,
  colore VARCHAR(30)  NOT NULL
);

CREATE TABLE Catalogo (
  fid   VARCHAR(50) NOT NULL,
  pid   VARCHAR(50) NOT NULL,
  costo REAL        NOT NULL CHECK (costo > 0),
  PRIMARY KEY (fid, pid),
  FOREIGN KEY (fid) REFERENCES Fornitori(fid),
  FOREIGN KEY (pid) REFERENCES Pezzi(pid)
);

/* =========================================================
   FORNITORI
   ========================================================= */
INSERT INTO Fornitori VALUES
('F01', 'Acme',          'Via Roma 1, Milano'),
('F02', 'WidgetCorp',    'Via Milano 2, Torino'),
('F03', 'Supplies Inc',  'Via Torino 3, Genova'),
('F04', 'TechParts',     'Via Venezia 4, Venezia'),
('F05', 'MegaSupplies',  'Via Napoli 5, Napoli'),
('F06', 'GreenTech',     'Via Palermo 6, Palermo'),
('F07', 'RedComponents', 'Via Firenze 7, Firenze');

/* =========================================================
   PEZZI
   ========================================================= */
INSERT INTO Pezzi VALUES
('P01', 'Bullone',        'rosso'),
('P02', 'Vite',           'blu'),
('P03', 'Dado',           'rosso'),
('P04', 'Rivetto',        'verde'),
('P05', 'Molla',          'blu'),
('P06', 'Guarnizione',    'rosso'),
('P07', 'Cuscinetto',     'verde'),
('P08', 'Cavo',           'blu'),
('P09', 'Resistore',      'rosso'),
('P10', 'Condensatore',   'verde'),
('P11', 'Trasformatore',  'verde'),
('P12', 'Fusibile',       'rosso');

/* =========================================================
   CATALOGO (CONSISTENTE E REALISTICO)
   ========================================================= */

/* Acme: fornitore generalista */
INSERT INTO Catalogo VALUES
('F01','P01',10.5),('F01','P02',5.1),('F01','P03',8.4),
('F01','P04',6.0),('F01','P05',7.3),('F01','P06',9.0),
('F01','P07',12.2),('F01','P08',4.6),('F01','P09',15.0),
('F01','P10',8.6),('F01','P11',18.0),('F01','P12',6.5);

/* WidgetCorp: meccanica */
INSERT INTO Catalogo VALUES
('F02','P01',11.0),('F02','P02',5.0),('F02','P03',8.1),
('F02','P04',6.7),('F02','P05',7.0),('F02','P07',11.8);

/* Supplies Inc: elettronica */
INSERT INTO Catalogo VALUES
('F03','P08',3.9),('F03','P09',14.8),('F03','P10',9.8),
('F03','P11',17.5),('F03','P12',6.2);

/* TechParts: solo verde */
INSERT INTO Catalogo VALUES
('F04','P04',6.1),('F04','P07',12.0),
('F04','P10',8.9),('F04','P11',18.3);

/* MegaSupplies: blu + rosso */
INSERT INTO Catalogo VALUES
('F05','P02',5.3),('F05','P05',7.4),
('F05','P06',9.2),('F05','P08',4.8);

/* GreenTech: solo verde */
INSERT INTO Catalogo VALUES
('F06','P04',6.4),('F06','P07',12.5),
('F06','P10',9.1);

/* RedComponents: solo rosso */
INSERT INTO Catalogo VALUES
('F07','P01',10.9),('F07','P03',8.3),
('F07','P06',9.1),('F07','P09',15.2),
('F07','P12',6.7);