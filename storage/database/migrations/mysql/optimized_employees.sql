
-- Index for the salaries table (speeds up WHERE to_date & salary)
CREATE INDEX idx_salaries_todate_salary_emp ON salaries(to_date, salary, emp_no);

-- Index for the dept_emp table (to speed up JOIN on emp_no & to_date)
CREATE INDEX idx_dept_emp_todate_emp ON dept_emp(to_date, emp_no);

-- Index to speed up JOIN of active positions
CREATE INDEX idx_titles_todate_emp ON titles(`to_date`, emp_no);
