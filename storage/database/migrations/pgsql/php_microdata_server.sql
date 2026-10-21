--
-- PostgreSQL database dump
--

\restrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU

-- Dumped from database version 16.14
-- Dumped by pg_dump version 18.6 (Ubuntu 18.6-0ubuntu0.26.04.1)

-- Started on 2026-10-21 20:32:18 WIB

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

DROP DATABASE IF EXISTS php_microdata_server;
--
-- TOC entry 3564 (class 1262 OID 17387)
-- Name: php_microdata_server; Type: DATABASE; Schema: -; Owner: postgres
--

CREATE DATABASE php_microdata_server WITH TEMPLATE = template0 ENCODING = 'UTF8' LOCALE_PROVIDER = libc LOCALE = 'en_US.utf8';


ALTER DATABASE php_microdata_server OWNER TO postgres;

\unrestrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU
\connect php_microdata_server
\restrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3565 (class 0 OID 0)
-- Name: php_microdata_server; Type: DATABASE PROPERTIES; Schema: -; Owner: postgres
--

ALTER DATABASE php_microdata_server SET search_path TO 'public', 'php_microdata_server';


\unrestrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU
\connect php_microdata_server
\restrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 6 (class 2615 OID 17430)
-- Name: php_microdata_server; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA php_microdata_server;


ALTER SCHEMA php_microdata_server OWNER TO postgres;

--
-- TOC entry 4 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: pg_database_owner
--

CREATE SCHEMA public;


ALTER SCHEMA public OWNER TO pg_database_owner;

--
-- TOC entry 3566 (class 0 OID 0)
-- Dependencies: 4
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: pg_database_owner
--

COMMENT ON SCHEMA public IS 'standard public schema';


--
-- TOC entry 858 (class 1247 OID 17432)
-- Name: event_queue_status; Type: TYPE; Schema: php_microdata_server; Owner: postgres
--

CREATE TYPE php_microdata_server.event_queue_status AS ENUM (
    'pending',
    'processing',
    'completed',
    'failed'
);


ALTER TYPE php_microdata_server.event_queue_status OWNER TO postgres;

--
-- TOC entry 233 (class 1255 OID 17557)
-- Name: on_update_current_timestamp_event_queue(); Type: FUNCTION; Schema: php_microdata_server; Owner: postgres
--

CREATE FUNCTION php_microdata_server.on_update_current_timestamp_event_queue() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
   NEW.updated_at = now();
   RETURN NEW;
END;
$$;


ALTER FUNCTION php_microdata_server.on_update_current_timestamp_event_queue() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 219 (class 1259 OID 17442)
-- Name: event_queue; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.event_queue (
    id bigint NOT NULL,
    user_id bigint,
    event_name character varying(255) NOT NULL,
    payload text NOT NULL,
    status php_microdata_server.event_queue_status DEFAULT 'pending'::php_microdata_server.event_queue_status,
    execution_time numeric(8,4) DEFAULT 0.0000,
    attempts bigint DEFAULT '0'::bigint,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone
);


ALTER TABLE public.event_queue OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 17441)
-- Name: event_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.event_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.event_queue_id_seq OWNER TO postgres;

--
-- TOC entry 3567 (class 0 OID 0)
-- Dependencies: 218
-- Name: event_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.event_queue_id_seq OWNED BY public.event_queue.id;


--
-- TOC entry 221 (class 1259 OID 17453)
-- Name: groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.groups (
    id bigint NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(50) NOT NULL,
    owner_id numeric,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone
);


ALTER TABLE public.groups OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 17452)
-- Name: groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.groups_id_seq OWNER TO postgres;

--
-- TOC entry 3568 (class 0 OID 0)
-- Dependencies: 220
-- Name: groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.groups_id_seq OWNED BY public.groups.id;


--
-- TOC entry 223 (class 1259 OID 17461)
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    created_at timestamp with time zone
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 17460)
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- TOC entry 3569 (class 0 OID 0)
-- Dependencies: 222
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- TOC entry 225 (class 1259 OID 17466)
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_id numeric NOT NULL,
    name character varying(255) NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp with time zone,
    expires_at timestamp with time zone,
    created_at timestamp with time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 17465)
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- TOC entry 3570 (class 0 OID 0)
-- Dependencies: 224
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- TOC entry 228 (class 1259 OID 17479)
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_permissions (
    role_id bigint NOT NULL,
    permission_id numeric NOT NULL
);


ALTER TABLE public.role_permissions OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 17473)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    description text,
    created_at timestamp with time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 17472)
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- TOC entry 3571 (class 0 OID 0)
-- Dependencies: 226
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- TOC entry 231 (class 1259 OID 17504)
-- Name: user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_roles (
    user_id numeric NOT NULL,
    role_id bigint NOT NULL,
    group_id bigint NOT NULL
);


ALTER TABLE public.user_roles OWNER TO postgres;

--
-- TOC entry 230 (class 1259 OID 17485)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    ulid character(26) DEFAULT NULL::bpchar,
    name character varying(255) NOT NULL,
    email character varying(255) DEFAULT ''::character varying NOT NULL,
    email_verified_at timestamp with time zone,
    password character varying(255) NOT NULL,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp with time zone,
    client_token character varying(100) DEFAULT NULL::character varying,
    remember_token character varying(100) DEFAULT NULL::character varying,
    current_team_id numeric,
    profile_photo_path character varying(2048) DEFAULT NULL::character varying,
    first_name character varying(100) DEFAULT NULL::character varying,
    last_name character varying(100) DEFAULT NULL::character varying,
    phone character varying(30) DEFAULT NULL::character varying,
    address_line1 character varying(150) DEFAULT NULL::character varying,
    address_line2 character varying(200) DEFAULT NULL::character varying,
    city character varying(100) DEFAULT NULL::character varying,
    default_url character varying(255) DEFAULT NULL::character varying,
    status boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    deleted_at timestamp with time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 17484)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 3572 (class 0 OID 0)
-- Dependencies: 229
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 232 (class 1259 OID 25988)
-- Name: v_user_permissions; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.v_user_permissions AS
 SELECT u.id AS user_id,
    u.name AS user_name,
    p.id AS permission_id,
    p.slug AS permission_slug,
    p.name AS permission_name,
    r.slug AS role_slug,
    ur.group_id,
    g.name AS group_name
   FROM (((((public.users u
     JOIN public.user_roles ur ON (((u.id)::numeric = ur.user_id)))
     JOIN public.roles r ON ((ur.role_id = r.id)))
     JOIN public.role_permissions rp ON ((r.id = rp.role_id)))
     JOIN public.permissions p ON ((rp.permission_id = (p.id)::numeric)))
     LEFT JOIN public.groups g ON ((ur.group_id = g.id)))
  WHERE (u.status = true);


ALTER VIEW public.v_user_permissions OWNER TO postgres;

--
-- TOC entry 3345 (class 2604 OID 17445)
-- Name: event_queue id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.event_queue ALTER COLUMN id SET DEFAULT nextval('public.event_queue_id_seq'::regclass);


--
-- TOC entry 3350 (class 2604 OID 17456)
-- Name: groups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups ALTER COLUMN id SET DEFAULT nextval('public.groups_id_seq'::regclass);


--
-- TOC entry 3352 (class 2604 OID 17464)
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- TOC entry 3353 (class 2604 OID 17469)
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- TOC entry 3354 (class 2604 OID 17476)
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- TOC entry 3355 (class 2604 OID 17488)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 3546 (class 0 OID 17442)
-- Dependencies: 219
-- Data for Name: event_queue; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 3548 (class 0 OID 17453)
-- Dependencies: 221
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.groups (id, name, code, owner_id, status, created_at) VALUES (1, 'Super Admin Pusat', 'SA-HQ-JKT', NULL, true, '2026-05-29 12:55:59+00') ON CONFLICT DO NOTHING;
INSERT INTO public.groups (id, name, code, owner_id, status, created_at) VALUES (2, 'Kantor Pusat Jakarta', 'HQ-JKT', NULL, true, '2026-05-29 12:55:59+00') ON CONFLICT DO NOTHING;
INSERT INTO public.groups (id, name, code, owner_id, status, created_at) VALUES (3, 'Gudang Surabaya', 'WH-SUB', NULL, true, '2026-05-29 12:55:59+00') ON CONFLICT DO NOTHING;


--
-- TOC entry 3550 (class 0 OID 17461)
-- Dependencies: 223
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.permissions (id, name, slug, created_at) VALUES (1, 'Create Asset', 'asset-create', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.permissions (id, name, slug, created_at) VALUES (2, 'View Asset', 'asset-view', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.permissions (id, name, slug, created_at) VALUES (3, 'Edit Asset', 'asset-edit', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.permissions (id, name, slug, created_at) VALUES (4, 'Delete Asset', 'asset-delete', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.permissions (id, name, slug, created_at) VALUES (5, 'Manage Users', 'user-manage', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.permissions (id, name, slug, created_at) VALUES (6, 'View Reports', 'report-view', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;


--
-- TOC entry 3552 (class 0 OID 17466)
-- Dependencies: 225
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- TOC entry 3555 (class 0 OID 17479)
-- Dependencies: 228
-- Data for Name: role_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 1) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (2, 1) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (3, 1) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 2) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (2, 2) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (3, 2) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (4, 2) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 3) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (2, 3) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 4) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 5) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (1, 6) ON CONFLICT DO NOTHING;
INSERT INTO public.role_permissions (role_id, permission_id) VALUES (2, 6) ON CONFLICT DO NOTHING;


--
-- TOC entry 3554 (class 0 OID 17473)
-- Dependencies: 227
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.roles (id, name, slug, description, created_at) VALUES (1, 'Super Admin', 'super-admin', 'Akses penuh seluruh sistem & grup', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.roles (id, name, slug, description, created_at) VALUES (2, 'Manager', 'manager', 'Kelola aset & laporan dalam grup', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.roles (id, name, slug, description, created_at) VALUES (3, 'Staff', 'staff', 'Hanya operasi dasar aset', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;
INSERT INTO public.roles (id, name, slug, description, created_at) VALUES (4, 'Guest', 'guest', 'Hanya lihat data tanpa ubah', '2026-05-29 12:58:26+00') ON CONFLICT DO NOTHING;


--
-- TOC entry 3558 (class 0 OID 17504)
-- Dependencies: 231
-- Data for Name: user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.user_roles (user_id, role_id, group_id) VALUES (1, 1, 1) ON CONFLICT DO NOTHING;
INSERT INTO public.user_roles (user_id, role_id, group_id) VALUES (2, 2, 2) ON CONFLICT DO NOTHING;
INSERT INTO public.user_roles (user_id, role_id, group_id) VALUES (3, 3, 3) ON CONFLICT DO NOTHING;
INSERT INTO public.user_roles (user_id, role_id, group_id) VALUES (4, 3, 3) ON CONFLICT DO NOTHING;
INSERT INTO public.user_roles (user_id, role_id, group_id) VALUES (4, 4, 2) ON CONFLICT DO NOTHING;


--
-- TOC entry 3557 (class 0 OID 17485)
-- Dependencies: 230
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.users (id, ulid, name, email, email_verified_at, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, client_token, remember_token, current_team_id, profile_photo_path, first_name, last_name, phone, address_line1, address_line2, city, default_url, status, created_at, updated_at, deleted_at) VALUES (1, NULL, 'Ahmad Admin', 'admin@demo.local', NULL, '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, true, '2026-05-29 12:55:15+00', NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public.users (id, ulid, name, email, email_verified_at, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, client_token, remember_token, current_team_id, profile_photo_path, first_name, last_name, phone, address_line1, address_line2, city, default_url, status, created_at, updated_at, deleted_at) VALUES (2, NULL, 'Budi Manager', 'budi@demo.local', NULL, '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, true, '2026-05-29 12:55:15+00', NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public.users (id, ulid, name, email, email_verified_at, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, client_token, remember_token, current_team_id, profile_photo_path, first_name, last_name, phone, address_line1, address_line2, city, default_url, status, created_at, updated_at, deleted_at) VALUES (3, NULL, 'Siti Staff', 'siti@demo.local', NULL, '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, true, '2026-05-29 12:55:15+00', NULL, NULL) ON CONFLICT DO NOTHING;
INSERT INTO public.users (id, ulid, name, email, email_verified_at, password, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, client_token, remember_token, current_team_id, profile_photo_path, first_name, last_name, phone, address_line1, address_line2, city, default_url, status, created_at, updated_at, deleted_at) VALUES (4, NULL, 'Dodi Freelance', 'dodi@demo.local', NULL, '$2y$10$4aj4jkDojd.2nvLiSYIJ4OBs3XKXF8seUJsoC.r7RIPCvG5iZKpum', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, true, '2026-05-29 12:55:15+00', NULL, NULL) ON CONFLICT DO NOTHING;


--
-- TOC entry 3573 (class 0 OID 0)
-- Dependencies: 218
-- Name: event_queue_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.event_queue_id_seq', 1, true);


--
-- TOC entry 3574 (class 0 OID 0)
-- Dependencies: 220
-- Name: groups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.groups_id_seq', 3, true);


--
-- TOC entry 3575 (class 0 OID 0)
-- Dependencies: 222
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 6, true);


--
-- TOC entry 3576 (class 0 OID 0)
-- Dependencies: 224
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 1, true);


--
-- TOC entry 3577 (class 0 OID 0)
-- Dependencies: 226
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 4, true);


--
-- TOC entry 3578 (class 0 OID 0)
-- Dependencies: 229
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 4, true);


--
-- TOC entry 3371 (class 2606 OID 17539)
-- Name: event_queue idx_17442_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.event_queue
    ADD CONSTRAINT idx_17442_primary PRIMARY KEY (id);


--
-- TOC entry 3376 (class 2606 OID 17540)
-- Name: groups idx_17453_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT idx_17453_primary PRIMARY KEY (id);


--
-- TOC entry 3379 (class 2606 OID 17534)
-- Name: permissions idx_17461_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT idx_17461_primary PRIMARY KEY (id);


--
-- TOC entry 3381 (class 2606 OID 17541)
-- Name: personal_access_tokens idx_17466_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT idx_17466_primary PRIMARY KEY (id);


--
-- TOC entry 3385 (class 2606 OID 17538)
-- Name: roles idx_17473_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT idx_17473_primary PRIMARY KEY (id);


--
-- TOC entry 3389 (class 2606 OID 17535)
-- Name: role_permissions idx_17479_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT idx_17479_primary PRIMARY KEY (role_id, permission_id);


--
-- TOC entry 3391 (class 2606 OID 17537)
-- Name: users idx_17485_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT idx_17485_primary PRIMARY KEY (id);


--
-- TOC entry 3396 (class 2606 OID 17536)
-- Name: user_roles idx_17504_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT idx_17504_primary PRIMARY KEY (user_id, role_id, group_id);


--
-- TOC entry 3369 (class 1259 OID 17522)
-- Name: idx_17442_idx_status_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17442_idx_status_created ON public.event_queue USING btree (status, created_at);


--
-- TOC entry 3372 (class 1259 OID 17521)
-- Name: idx_17442_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17442_user_id ON public.event_queue USING btree (user_id);


--
-- TOC entry 3373 (class 1259 OID 17526)
-- Name: idx_17453_fk_groups_owner; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17453_fk_groups_owner ON public.groups USING btree (owner_id);


--
-- TOC entry 3374 (class 1259 OID 17523)
-- Name: idx_17453_groups_code_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_17453_groups_code_unique ON public.groups USING btree (code);


--
-- TOC entry 3377 (class 1259 OID 17509)
-- Name: idx_17461_permissions_slug_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_17461_permissions_slug_unique ON public.permissions USING btree (slug);


--
-- TOC entry 3382 (class 1259 OID 17527)
-- Name: idx_17466_tokens_token_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_17466_tokens_token_unique ON public.personal_access_tokens USING btree (token);


--
-- TOC entry 3383 (class 1259 OID 17525)
-- Name: idx_17466_tokens_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17466_tokens_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_id);


--
-- TOC entry 3386 (class 1259 OID 17518)
-- Name: idx_17473_roles_slug_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_17473_roles_slug_unique ON public.roles USING btree (slug);


--
-- TOC entry 3387 (class 1259 OID 17512)
-- Name: idx_17479_fk_rp_permission; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17479_fk_rp_permission ON public.role_permissions USING btree (permission_id);


--
-- TOC entry 3392 (class 1259 OID 17520)
-- Name: idx_17485_users_email_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_17485_users_email_unique ON public.users USING btree (email);


--
-- TOC entry 3393 (class 1259 OID 17513)
-- Name: idx_17504_fk_ur_group; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17504_fk_ur_group ON public.user_roles USING btree (group_id);


--
-- TOC entry 3394 (class 1259 OID 17517)
-- Name: idx_17504_fk_ur_role; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_17504_fk_ur_role ON public.user_roles USING btree (role_id);


--
-- TOC entry 3400 (class 2620 OID 17558)
-- Name: event_queue on_update_current_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON public.event_queue FOR EACH ROW EXECUTE FUNCTION php_microdata_server.on_update_current_timestamp_event_queue();


--
-- TOC entry 3397 (class 2606 OID 17542)
-- Name: role_permissions fk_rp_role; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES public.roles(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- TOC entry 3398 (class 2606 OID 17547)
-- Name: user_roles fk_ur_group; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT fk_ur_group FOREIGN KEY (group_id) REFERENCES public.groups(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- TOC entry 3399 (class 2606 OID 17552)
-- Name: user_roles fk_ur_role; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES public.roles(id) ON UPDATE RESTRICT ON DELETE CASCADE;


-- Completed on 2026-10-21 20:32:19 WIB

--
-- PostgreSQL database dump complete
--

\unrestrict BRdPRssebZacafAfs9hr7iKZBI2JoiXDwoJudTzNWoYf14uh4K47hDtBotoLZXU

