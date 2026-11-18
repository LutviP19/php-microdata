--  Sample employee database 
--  See changelog table for details
--  Copyright (C) 2007,2008, MySQL AB
--  
--  Original data created by Fusheng Wang and Carlo Zaniolo
--  http://www.cs.aau.dk/TimeCenter/software.htm
--  http://www.cs.aau.dk/TimeCenter/Data/employeeTemporalDataSet.zip
-- 
--  Current schema by Giuseppe Maxia 
--  Data conversion from XML to relational by Patrick Crews
-- 
-- This work is licensed under the 
-- Creative Commons Attribution-Share Alike 3.0 Unported License. 
-- To view a copy of this license, visit 
-- http://creativecommons.org/licenses/by-sa/3.0/ or send a letter to 
-- Creative Commons, 171 Second Street, Suite 300, San Francisco, 
-- California, 94105, USA.
-- 
--  DISCLAIMER
--  To the best of our knowledge, this data is fabricated, and
--  it does not correspond to real people. 
--  Any similarity to existing people is purely coincidental.
-- 

DROP DATABASE IF EXISTS employees;
CREATE DATABASE IF NOT EXISTS employees;
USE employees;

SELECT 'CREATING DATABASE STRUCTURE' as 'INFO';

DROP TABLE IF EXISTS dept_emp,
                     dept_manager,
                     titles,
                     salaries, 
                     employees, 
                     departments;


CREATE TABLE employees (
    emp_no      INT             NOT NULL,
    birth_date  DATE            NOT NULL,
    first_name  VARCHAR(14)     NOT NULL,
    last_name   VARCHAR(16)     NOT NULL,
    gender      ENUM ('M','F')  NOT NULL,    
    hire_date   DATE            NOT NULL,
    PRIMARY KEY (emp_no)
);

CREATE TABLE departments (
    dept_no     CHAR(4)         NOT NULL,
    dept_name   VARCHAR(40)     NOT NULL,
    PRIMARY KEY (dept_no),
    UNIQUE  KEY (dept_name)
);

CREATE TABLE dept_manager (
   emp_no       INT             NOT NULL,
   dept_no      CHAR(4)         NOT NULL,
   from_date    DATE            NOT NULL,
   `to_date`      DATE            NOT NULL,
   FOREIGN KEY (emp_no)  REFERENCES employees (emp_no)    ON DELETE CASCADE,
   FOREIGN KEY (dept_no) REFERENCES departments (dept_no) ON DELETE CASCADE,
   PRIMARY KEY (emp_no,dept_no)
); 

CREATE TABLE dept_emp (
    emp_no      INT             NOT NULL,
    dept_no     CHAR(4)         NOT NULL,
    from_date   DATE            NOT NULL,
    `to_date`     DATE            NOT NULL,
    FOREIGN KEY (emp_no)  REFERENCES employees   (emp_no)  ON DELETE CASCADE,
    FOREIGN KEY (dept_no) REFERENCES departments (dept_no) ON DELETE CASCADE,
    PRIMARY KEY (emp_no,dept_no)
);

CREATE TABLE titles (
    emp_no      INT             NOT NULL,
    title       VARCHAR(50)     NOT NULL,
    from_date   DATE            NOT NULL,
    `to_date`     DATE,
    FOREIGN KEY (emp_no) REFERENCES employees (emp_no) ON DELETE CASCADE,
    PRIMARY KEY (emp_no,title, from_date)
) 
; 

CREATE TABLE salaries (
    emp_no      INT             NOT NULL,
    salary      INT             NOT NULL,
    from_date   DATE            NOT NULL,
    `to_date`     DATE            NOT NULL,
    FOREIGN KEY (emp_no) REFERENCES employees (emp_no) ON DELETE CASCADE,
    PRIMARY KEY (emp_no, from_date)
) 
; 

CREATE OR REPLACE VIEW dept_emp_latest_date AS
    SELECT emp_no, MAX(from_date) AS from_date, MAX(`to_date`) AS `to_date`
    FROM dept_emp
    GROUP BY emp_no;

# shows only the current department for each employee
CREATE OR REPLACE VIEW current_dept_emp AS
    SELECT l.emp_no, dept_no, l.from_date, l.`to_date`
    FROM dept_emp d
        INNER JOIN dept_emp_latest_date l
        ON d.emp_no=l.emp_no AND d.from_date=l.from_date AND l.`to_date` = d.`to_date`;

flush /*!50503 binary */ logs;

-- SELECT 'LOADING departments' as 'INFO';
-- source load_departments.dump ;
-- SELECT 'LOADING employees' as 'INFO';
-- source load_employees.dump ;
-- SELECT 'LOADING dept_emp' as 'INFO';
-- source load_dept_emp.dump ;
-- SELECT 'LOADING dept_manager' as 'INFO';
-- source load_dept_manager.dump ;
-- SELECT 'LOADING titles' as 'INFO';
-- source load_titles.dump ;
-- SELECT 'LOADING salaries' as 'INFO';
-- source load_salaries1.dump ;
-- source load_salaries2.dump ;
-- source load_salaries3.dump ;

-- source show_elapsed.sql ;



-- 1. Bersihkan indeks yang redundan / tidak perlu
DROP INDEX IF EXISTS idx_employees_emp_no;
DROP INDEX IF EXISTS idx_salaries_emp_no; -- Diganti dengan yang lebih spesifik di bawah
DROP INDEX IF EXISTS idx_salaries_current_top; -- Diganti dengan yang lebih spesifik di bawah
DROP INDEX IF EXISTS idx_dept_emp_current; -- Diganti dengan yang lebih spesifik di bawah
DROP INDEX IF EXISTS idx_titles_current; -- Diganti dengan yang lebih spesifik di bawah

-- ==========================================
-- 2. PEMBUATAN INDEKS YANG BERSIH & OPTIMAL
-- ==========================================

-- A. Tabel employees
-- Indeks untuk mempercepat pencarian berdasarkan nama lengkap
CREATE INDEX idx_employees_name 
ON public.employees (last_name, first_name);


-- B. Tabel dept_manager
-- Indeks untuk mempercepat pencarian manajer berdasarkan departemen
CREATE INDEX idx_dept_manager_dept 
ON public.dept_manager (dept_no, to_date);


-- C. Tabel dept_emp (Partial Indexes untuk data aktif)
-- Indeks 1: Jika pencarian difilter berdasarkan departemen terlebih dahulu
CREATE INDEX idx_dept_emp_current_dept 
ON public.dept_emp (dept_no, emp_no) 
WHERE to_date = '9999-01-01';

-- Indeks 2: Jika pencarian difilter berdasarkan emp_no terlebih dahulu
CREATE INDEX idx_dept_emp_current 
ON public.dept_emp (emp_no, dept_no) 
WHERE to_date = '9999-01-01';


-- D. Tabel titles (Partial Index untuk jabatan aktif)
CREATE INDEX idx_titles_current 
ON public.titles (emp_no) 
WHERE to_date = '9999-01-01';

-- (Opsional tambahan jika sering filter berdasarkan nama title aktif)
CREATE INDEX idx_titles_current_title 
ON public.titles (title, emp_no) 
WHERE to_date = '9999-01-01';


-- E. Tabel salaries (Partial & Covering Index untuk gaji aktif)
-- Menggabungkan fungsi pengurutan gaji tertinggi dan pencarian pegawai aktif
CREATE INDEX idx_salaries_current_salary 
ON public.salaries (salary DESC, emp_no) 
INCLUDE (salary) 
WHERE to_date = '9999-01-01';


-- Summary Table yang diperbarui secara otomatis menggunakan Database Triggers
CREATE TABLE dept_salary_summary (
    dept_no CHAR(4) PRIMARY KEY,
    avg_salary NUMERIC(12, 2) NOT NULL,
    emp_count INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buat indeks opsional jika diperlukan untuk pencarian cepat
CREATE INDEX idx_summary_dept ON dept_salary_summary (dept_no);

CREATE OR REPLACE FUNCTION fn_update_dept_salary_summary()
RETURNS TRIGGER AS $$
DECLARE
    v_dept_no CHAR(4);
BEGIN
    -- 1. Identifikasi dept_no yang mengalami perubahan data
    IF (TG_OP = 'DELETE') THEN
        SELECT dept_no INTO v_dept_no 
        FROM dept_emp 
        WHERE emp_no = OLD.emp_no AND to_date = '9999-01-01';
    ELSE
        SELECT dept_no INTO v_dept_no 
        FROM dept_emp 
        WHERE emp_no = NEW.emp_no AND to_date = '9999-01-01';
    END IF;

    -- 2. Jika karyawan aktif memiliki departemen, perbarui summary untuk dept tersebut
    IF v_dept_no IS NOT NULL THEN
        INSERT INTO dept_salary_summary (dept_no, avg_salary, emp_count, last_updated)
        SELECT 
            v_dept_no,
            COALESCE(AVG(s.salary), 0),
            COUNT(s.emp_no),
            CURRENT_TIMESTAMP
        FROM salaries s
        JOIN dept_emp de ON s.emp_no = de.emp_no
        WHERE de.dept_no = v_dept_no 
          AND s.to_date = '9999-01-01' 
          AND de.to_date = '9999-01-01'
        GROUP BY de.dept_no
        ON CONFLICT (dept_no) 
        DO UPDATE SET 
            avg_salary = EXCLUDED.avg_salary,
            emp_count = EXCLUDED.emp_count,
            last_updated = CURRENT_TIMESTAMP;
    END IF;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_update_dept_salary_summary_bulk()
RETURNS TRIGGER AS $$
BEGIN
    -- Update summary secara massal berdasarkan departemen dari baris-baris yang di-insert
    INSERT INTO dept_salary_summary (dept_no, avg_salary, emp_count, last_updated)
    SELECT 
        de.dept_no,
        AVG(s.salary) AS avg_salary,
        COUNT(s.emp_no) AS emp_count,
        CURRENT_TIMESTAMP
    FROM salaries s
    JOIN dept_emp de ON s.emp_no = de.emp_no
    WHERE de.dept_no IN (
        SELECT DISTINCT d.dept_no 
        FROM dept_emp d 
        JOIN inserted_rows i ON d.emp_no = i.emp_no 
        WHERE d.to_date = '9999-01-01'
    )
      AND s.to_date = '9999-01-01' 
      AND de.to_date = '9999-01-01'
    GROUP BY de.dept_no
    ON CONFLICT (dept_no) 
    DO UPDATE SET 
        avg_salary = EXCLUDED.avg_salary,
        emp_count = EXCLUDED.emp_count,
        last_updated = CURRENT_TIMESTAMP;

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Trigger untuk transaksi harian / single row 
DROP TRIGGER IF EXISTS trg_salaries_summary_update ON salaries;
CREATE TRIGGER trg_salaries_summary_update
AFTER INSERT OR UPDATE OR DELETE ON salaries
FOR EACH ROW
EXECUTE FUNCTION fn_update_dept_salary_summary();

DROP TRIGGER IF EXISTS trg_dept_emp_summary_update ON dept_emp;
CREATE TRIGGER trg_dept_emp_summary_update
AFTER INSERT OR UPDATE OR DELETE ON dept_emp
FOR EACH ROW
EXECUTE FUNCTION fn_update_dept_salary_summary();

-- Trigger khusus untuk bulk insert / multi-row statement
DROP TRIGGER IF EXISTS trg_salaries_bulk_summary_update ON salaries;
CREATE TRIGGER trg_salaries_bulk_summary_update
AFTER INSERT ON salaries
REFERENCING NEW TABLE AS inserted_rows
FOR EACH STATEMENT
EXECUTE FUNCTION fn_update_dept_salary_summary_bulk();

-- Jalankan sekali query kalkulasi ulang untuk menyamakan data summary
TRUNCATE TABLE dept_salary_summary;
INSERT INTO dept_salary_summary (dept_no, avg_salary, emp_count, last_updated)
SELECT 
    de.dept_no,
    AVG(s.salary) AS avg_salary,
    COUNT(s.emp_no) AS emp_count,
    CURRENT_TIMESTAMP
FROM salaries s
JOIN dept_emp de ON s.emp_no = de.emp_no
WHERE s.to_date = '9999-01-01' 
  AND de.to_date = '9999-01-01'
GROUP BY de.dept_no;




