CREATE TABLE IF NOT EXISTS Pays (
    code_pays CHAR(2) PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS Motifs (
    code_motif INT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS Formes_Juridiques (
    code_forme INT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);
CREATE TABLE IF NOT EXISTS Clients (
    code_client INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    code_forme INT,
    date_naissance DATE,
    num_sec_soc VARCHAR(15),
    code_pays CHAR(2),
    date_entree DATE,
    code_motif INT,
    FOREIGN KEY (code_forme) REFERENCES Formes_Juridiques(code_forme),
    FOREIGN KEY (code_pays) REFERENCES Pays(code_pays),
    FOREIGN KEY (code_motif) REFERENCES Motifs(code_motif)
);

CREATE TABLE IF NOT EXISTS Unites(
    code_unite CHAR PRIMARY KEY,
    libelle VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS TVA(
    code_tva INT PRIMARY KEY,
    taux DECIMAL (4, 2)
);

CREATE TABLE IF NOT EXISTS Articles(
    code_article VARCHAR(3) PRIMARY KEY,
    designation VARCHAR(50),
    code_unite CHAR,
    code_tva INT,
    forfait_ht DECIMAL(10, 2),
    FOREIGN KEY (code_unite) REFERENCES Unites(code_unite),
    FOREIGN KEY (code_tva) REFERENCES TVA(code_tva)
);

CREATE TABLE IF NOT EXISTS Devis(
    code_devis INT PRIMARY KEY AUTO_INCREMENT,
    code_client INT,
    status_devis INT,
    date_devis DATE,
    montant_ht DECIMAL(10, 2),
    montant_ttc DECIMAL(10, 2),
    status_devis INT NOT NULL DEFAULT 0,
    FOREIGN KEY (code_client) REFERENCES Clients(code_client)
);

CREATE TABLE IF NOT EXISTS Lignes_Devis(
    code_ligne INT PRIMARY KEY AUTO_INCREMENT,
    code_devis INT,
    code_article VARCHAR(3),
    quantite INT,
    prix_unitaire_ht DECIMAL(10, 2),
    montant_ht DECIMAL(10, 2),
    FOREIGN KEY (code_devis) REFERENCES Devis(code_devis),
    FOREIGN KEY (code_article) REFERENCES Articles(code_article)
);

CREATE TABLE IF NOT EXISTS Files(
    id_file INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(255), -- pdf/ image ect
    file_nature VARCHAR(255), -- cni, devis, contrat ect
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_size INT,
    code_devis INT,
    code_client INT,
    FOREIGN KEY (code_devis) REFERENCES Devis(code_devis),
    FOREIGN KEY (code_client) REFERENCES Clients(code_client)
);


INSERT IGNORE INTO Pays (code_pays, libelle) VALUES
('FR', 'France'),
('BE', 'Belgique'),
('MA', 'Maroc'),
('TN', 'Tunisie'),
('DZ', 'Algérie');

INSERT IGNORE INTO Motifs (code_motif, libelle) VALUES
(1, 'Téléphone'),
(2, 'Mail'),
(3, 'Web');

INSERT IGNORE INTO Formes_Juridiques (code_forme, libelle) VALUES
(1, 'Micro-Entreprise'),
(2, 'Entreprise Individuelle'),
(3, 'Entreprise Individuelle à responsabilité limitée'),
(4, 'Entreprise unipersonnelle à responsabilité limitée'),
(5, 'Société par actions simplifiée unipersonnelle'),
(6, 'Société à responsabilité limitée'),
(7, 'Société par actions simplifiée'),
(8, 'Société anonyme');

INSERT IGNORE INTO Clients (code_client, nom, prenom, code_forme, date_naissance, num_sec_soc, code_pays, date_entree, code_motif) VALUES
(1, 'SY', 'Omar', 1, '1978-01-20', '178017830240455', 'FR', '2023-02-01', 1),
(2, 'DEPARDIEU', 'Gérard', 8, '1948-12-27', '148127504406759', 'FR', '2023-04-05', 2),
(3, 'DUJARDIN', 'Jean', 2, '1972-06-19', '172065903800855', 'FR', '2023-06-12', 3),
(4, 'RENO', 'Jean', 7, '1948-07-30', NULL, 'MA', '2023-08-18', 1),
(5, 'COTILLARD', 'Marion', 3, '1975-09-30', '275097503200542', 'FR', '2023-09-26', 1),
(6, 'CASSEL', 'Vincent', 6, '1966-11-23', '166117500600711', 'FR', '2023-01-01', 3),
(7, 'GREEN', 'Eva', 4, '1980-06-17', '280067500400733', 'FR', '2023-11-15', 2),
(8, 'EFIRA', 'Virginie', 5, '1977-05-05', NULL, 'BE', '2023-10-30', 2);

INSERT IGNORE INTO Unites (code_unite, libelle) VALUES
('S', 'Secondes'),
('M', 'Minutes'),
('H', 'Heures');

INSERT IGNORE INTO TVA (code_tva, taux) VALUES
(0, 0),
(1, 20),
(2, 10),
(3, 5.5);

INSERT IGNORE INTO Articles (code_article, designation, code_unite, forfait_ht, code_tva) VALUES
('A1', 'Intervention à l''heure', 'H', 72, 1),
('A2', 'Intervention à la minute', 'M', 1.2, 2),
('A3', 'Intervention à la seconde', 'S', 0.02, 3);

