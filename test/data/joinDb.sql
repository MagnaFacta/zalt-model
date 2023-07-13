
CREATE TABLE companies (
    cid INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(10)
);

CREATE TABLE family (
    fid INTEGER NOT NULL PRIMARY KEY,
    name VARCHAR(10),
    fparent1 INTEGER NULL,
    fparent2 INTEGER NULL,
    cwork INTEGER NULL
);

CREATE TABLE marriage (
    fid1 INTEGER NOT NULL,
    fid2 INTEGER NOT NULL,
    start VARCHAR(20) NOT NULL,
    until VARCHAR(20),
    PRIMARY KEY (fid1, fid2)
);
