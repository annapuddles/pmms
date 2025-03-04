DROP TABLE IF EXISTS queue;
DROP TABLE IF EXISTS room;
DROP TABLE IF EXISTS catalog_genre;
DROP TABLE IF EXISTS catalog;
DROP TABLE IF EXISTS source;
DROP TABLE IF EXISTS genre;
DROP TABLE IF EXISTS captions;
DROP VIEW IF EXISTS catalog_with_genre;
DROP VIEW IF EXISTS family_catalog;
DROP VIEW IF EXISTS family_catalog_with_genre;

CREATE TABLE room (
	id INTEGER AUTO_INCREMENT,
	room_key VARCHAR(36) NOT NULL,
	url VARCHAR(1024) NOT NULL DEFAULT '',
	title VARCHAR(255),
	start_time BIGINT NOT NULL,
	paused BIGINT,
	loop_media BOOLEAN NOT NULL DEFAULT FALSE,
	expires DATETIME,
	owner VARCHAR(127),
	locked BOOLEAN NOT NULL DEFAULT FALSE,
	PRIMARY KEY (id)
);

CREATE TABLE queue (
	id INTEGER AUTO_INCREMENT,
	room_id INTEGER,
	url VARCHAR(1024),
	title VARCHAR(255),
	PRIMARY KEY (id),
	FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE CASCADE
);

CREATE TABLE catalog (
	id INTEGER AUTO_INCREMENT,
	url VARCHAR(1024),
	title VARCHAR(255) NOT NULL,
	sort_title VARCHAR(255) NOT NULL,
	cover VARCHAR(255),
	category ENUM ('movie', 'tv', 'music') NOT NULL,
	series INTEGER REFERENCES catalog (id),
	keywords VARCHAR(255) NOT NULL DEFAULT '',
	hidden BOOLEAN NOT NULL DEFAULT FALSE,
	PRIMARY KEY (id),
	FULLTEXT search (sort_title, keywords)
);

CREATE TABLE source (
	id INTEGER AUTO_INCREMENT,
	url VARCHAR(1024) NOT NULL,
	source_name VARCHAR(16) NOT NULL,
	source_url VARCHAR(1024) NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE genre (
	id INTEGER AUTO_INCREMENT,
	name VARCHAR(16) NOT NULL,
	PRIMARY KEY (id)
);

INSERT INTO genre (id, name) VALUES (1, 'Family');
INSERT INTO genre (name) VALUES
	('Mystery'),
	('Western'),
	('Comedy'),
	('Animation'),
	('Adventure'),
	('Action'),
	('Crime'),
	('Drama'),
	('Horror'),
	('Christmas'),
	('Sci Fi'),
	('Anime'),
	('Adult');

CREATE TABLE catalog_genre (
	catalog_id INTEGER,
	genre_id INTEGER,
	PRIMARY KEY (catalog_id, genre_id),
	FOREIGN KEY (catalog_id) REFERENCES catalog (id) ON DELETE CASCADE,
	FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE
);

CREATE TABLE captions (
	id INTEGER AUTO_INCREMENT,
	url VARCHAR(1024) NOT NULL,
	captions_name VARCHAR(16) NOT NULL,
	captions_url VARCHAR(1024) NOT NULL,
	PRIMARY KEY (id)
);

CREATE VIEW catalog_with_genre AS SELECT catalog.id AS id, url, title, sort_title, cover, category, series, keywords, hidden, genre.name AS genre FROM catalog JOIN catalog_genre ON catalog.id = catalog_genre.catalog_id JOIN genre ON genre.id = catalog_genre.genre_id;


CREATE VIEW family_catalog AS SELECT catalog.id, url, title, sort_title, cover, category, series, keywords, hidden FROM catalog JOIN catalog_genre ON catalog.id = catalog_genre.catalog_id WHERE genre_id = 1;

CREATE VIEW family_catalog_with_genre AS SELECT catalog.id, url, title, sort_title, cover, category, series, keywords, hidden, genre.name AS genre FROM catalog JOIN catalog_genre ON catalog.id = catalog_genre.catalog_id JOIN genre ON genre.id = catalog_genre.genre_id JOIN catalog_genre x ON catalog.id = x.catalog_id WHERE x.genre_id = 1;
