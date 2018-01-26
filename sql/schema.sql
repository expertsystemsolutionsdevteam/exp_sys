BEGIN;

CREATE SEQUENCE exp_sys.user_id_seq START 100;
CREATE TABLE exp_sys.user
(
  user_id integer NOT NULL DEFAULT nextval('exp_sys.user_id_seq'),
  user_name varchar(100) NOT NULL,
  email text,
  first_name text,
  last_name text,
  json_data jsonb,
  CONSTRAINT pk_exp_sys__user_id PRIMARY KEY (user_id)
);


CREATE SEQUENCE exp_sys.phone_detail_id_seq START 100;
CREATE TABLE exp_sys.phone_detail
(
  phone_detail_id integer NOT NULL DEFAULT nextval('exp_sys.phone_detail_id_seq'),
  user_id integer       REFERENCES exp_sys.user (user_id),
  phone_number text,
  CONSTRAINT pk_exp_sys__phone_detail_id PRIMARY KEY (phone_detail_id)
);
CREATE INDEX phone_detail__phone_number_idx ON exp_sys.phone_detail (phone_number);

CREATE SEQUENCE exp_sys.api_key_id_seq START 100;
CREATE TABLE exp_sys.api_key
(
  api_key_id integer NOT NULL DEFAULT nextval('exp_sys.api_key_id_seq'),
  user_id integer       REFERENCES exp_sys.user (user_id),
  api_key text,
  secret text,
  date_range tsrange,
  EXCLUDE USING gist (user_id WITH =, date_range WITH &&),
  CONSTRAINT pk_exp_sys__api_key_id PRIMARY KEY (api_key_id)
);
CREATE INDEX api_key__date_range_idx ON exp_sys.api_key USING gist (user_id, date_range);


CREATE SEQUENCE exp_sys.api_log_id_seq START 100;
CREATE TABLE exp_sys.api_log
(
  api_log_id integer NOT NULL DEFAULT nextval('exp_sys.api_log_id_seq'),
  user_id integer       REFERENCES exp_sys.user (user_id),
  date_time timestamp,
  api_url TEXT,
  json_data jsonb,
  CONSTRAINT pk_exp_sys__api_log_id PRIMARY KEY (api_log_id)
);
CREATE INDEX api_log__date_time_idx ON exp_sys.api_log (date_time);


CREATE SEQUENCE exp_sys.category_id_seq START 100;
CREATE TABLE exp_sys.category
(
  category_id integer NOT NULL DEFAULT nextval('exp_sys.category_id_seq'),
  name varchar(100) NOT NULL,
  description text,
  CONSTRAINT pk_exp_sys__category_id PRIMARY KEY (category_id)
);
CREATE INDEX category__name_idx ON exp_sys.category (name);


-- CREATE SEQUENCE exp_sys.tag_id_seq START 100;
-- CREATE TABLE exp_sys.tag
-- (
--   tag_id integer NOT NULL DEFAULT nextval('exp_sys.tag_id_seq'),
--   name varchar(100) NOT NULL,
--   description text,
--   CONSTRAINT pk_exp_sys__tag_id PRIMARY KEY (tag_id)
-- );
-- CREATE INDEX tag__name_idx ON exp_sys.tag (name);


CREATE SEQUENCE exp_sys.exp_sys_id_seq START 100;
CREATE TABLE exp_sys.exp_sys
(
  exp_sys_id integer NOT NULL DEFAULT nextval('exp_sys.exp_sys_id_seq'),
  name varchar(100) NOT NULL,
  description text,
  user_id integer NOT NULL      REFERENCES exp_sys.user (user_id),
  gojs_class text, 
  json_data jsonb,
  CONSTRAINT pk_exp_sys__exp_sys_id PRIMARY KEY (exp_sys_id)
);


-- CREATE SEQUENCE exp_sys.tag_detail_id_seq START 100;
-- CREATE TABLE exp_sys.tag_detail
-- (
--   tag_detail_id integer NOT NULL DEFAULT nextval('exp_sys.tag_detail_id_seq'),
--   exp_sys_id integer NOT NULL   REFERENCES exp_sys.exp_sys (exp_sys_id),
--   tag_id integer                REFERENCES exp_sys.tag (tag_id),
--   CONSTRAINT pk_exp_sys__tag_detail_id PRIMARY KEY (tag_detail_id)
-- );


CREATE SEQUENCE exp_sys.category_detail_id_seq START 100;
CREATE TABLE exp_sys.category_detail
(
  category_detail_id integer NOT NULL DEFAULT nextval('exp_sys.category_detail_id_seq'),
  exp_sys_id integer NOT NULL         REFERENCES exp_sys.exp_sys (exp_sys_id),
  category_id integer                 REFERENCES exp_sys.category (category_id),
  CONSTRAINT pk_exp_sys__category_detail_id PRIMARY KEY (category_detail_id)
);


CREATE SEQUENCE exp_sys.dclass_type_id_seq START 100;
CREATE TABLE exp_sys.dclass_type
(
  dclass_type_id integer NOT NULL DEFAULT nextval('exp_sys.dclass_type_id_seq'),
  name varchar(100) NOT NULL,
  description text,
  CONSTRAINT pk_exp_sys__dclass_type_id PRIMARY KEY (dclass_type_id)
);


CREATE SEQUENCE exp_sys.node_id_seq START 100;
CREATE TABLE exp_sys.node
(
  node_id bigint NOT NULL DEFAULT nextval('exp_sys.node_id_seq'),
  exp_sys_id integer          REFERENCES exp_sys.exp_sys (exp_sys_id),
  name varchar(100) NOT NULL,
  level integer NOT NULL,
  description text,
  tags jsonb,
  dclass_type_id integer      REFERENCES exp_sys.dclass_type (dclass_type_id),
  json_data jsonb,
  CONSTRAINT pk_node__node_id PRIMARY KEY (node_id)
);


CREATE SEQUENCE exp_sys.node_links_id_seq START 100;
CREATE TABLE exp_sys.node_links
(
  node_links_id bigint NOT NULL DEFAULT nextval('exp_sys.node_links_id_seq'),
  node_id bigint          REFERENCES exp_sys.node (node_id),
  to_node_id bigint       REFERENCES exp_sys.node (node_id),
  CONSTRAINT pk_node_links__node_links_id PRIMARY KEY (node_links_id)
);

CREATE SEQUENCE exp_sys.node_attachment_id_seq START 100;
CREATE TABLE exp_sys.node_attachment
(
  node_attachment_id bigint NOT NULL DEFAULT nextval('exp_sys.node_attachment_id_seq'),
  node_id bigint          REFERENCES exp_sys.node (node_id),
  path_to_attachment text,
  type text,
  CONSTRAINT pk_node_attachment__node_attachment_id PRIMARY KEY (node_attachment_id)
);


INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (1, 'D-Tree Type 1 - Condition/Action', '');
INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (2, 'D-Tree Type 2 - Condition/Sequence', '');
INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (3, 'D-Tree Type 3 - Output/Input keys', '');
INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (4, 'D-Tree Type 4 - Variable/Action', '');
INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (5, 'D-Tree Type 5 - Complex decision making', '');
INSERT INTO exp_sys.dclass_type (dclass_type_id, name, description)
  VALUES (6, 'D-Tree Type 6 - Logical decision making', '');

INSERT INTO exp_sys.user (user_id, user_name, email, first_name, last_name) 
  VALUES (1, 'martyi', 'martin.israelsen@gmail.com', 'Marty', 'Israelsen');

INSERT INTO exp_sys.api_key (api_key_id, user_id, api_key, secret, date_range) 
  VALUES (1, 1, 'TESTKEY', 'secret_sauce', '[2017-01-01,)');

INSERT INTO exp_sys.exp_sys (exp_sys_id, name, description, user_id, gojs_class)  
  VALUES (1, 'Mac Computer Expert System', 'Holds all info about Mac Computers', 1, '');

INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (1, 1, 'How do I tell if I am on a Mac Computer?', 1, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (2, 1, 'How do I left click on a Mac Computer?', 2, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (3, 1, 'How do I right click on a Mac Computer?', 2, '', '{}', 2);

INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (1, 1, 2);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (2, 1, 3);


INSERT INTO exp_sys.exp_sys (exp_sys_id, name, description, user_id, gojs_class)  
  VALUES (3, 'Stop Light Expert System', 'How to proceed as a stop light.', 1, '');

INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (20, 3, 'Approach Traffic Light', 1, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (21, 3, 'Light is Red', 2, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (22, 3, 'Light is Yellow', 2, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (23, 3, 'Light is Green', 2, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (24, 3, 'Intersection Occupied', 3, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (25, 3, 'Intersection Not Occupied', 3, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (26, 3, 'Slow down', 3, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (27, 3, 'Go', 3, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (28, 3, 'Emergency Mission', 3, '', '{}', 2);
INSERT INTO exp_sys.node (node_id, exp_sys_id, name, level, description, tags, dclass_type_id)  
  VALUES (29, 3, 'Stop', 3, '', '{}', 2);


INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (20, 20, 21);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (21, 20, 22);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (22, 20, 23);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (23, 23, 24);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (24, 22, 26);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (25, 22, 27);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (26, 21, 28);
INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
  VALUES (27, 21, 29);
--INSERT INTO exp_sys.node_links (node_links_id, node_id, to_node_id)  
--  VALUES (28, 29, 1);


COMMIT;