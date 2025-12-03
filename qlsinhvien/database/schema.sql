-- Hệ thống Quản lý Sinh viên
-- Database Schema

DROP DATABASE IF EXISTS qlSinhVien;
CREATE DATABASE qlSinhVien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE qlSinhVien;

-- Bảng người dùng (users)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng sinh viên (students)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_code VARCHAR(20) UNIQUE NOT NULL,
    class VARCHAR(50),
    major VARCHAR(100),
    enrollment_year INT,
    gpa DECIMAL(3,2) DEFAULT 0.00,
    total_credits INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student_code (student_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng giảng viên (teachers)
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teacher_code VARCHAR(20) UNIQUE NOT NULL,
    department VARCHAR(100),
    position VARCHAR(50),
    specialization VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_teacher_code (teacher_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng kỳ học (semesters)
CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_name VARCHAR(50) NOT NULL,
    semester_code VARCHAR(20) UNIQUE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active ENUM('yes', 'no') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng học phần (courses)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    credits INT NOT NULL,
    max_students INT DEFAULT 50,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_code (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lớp học phần (class_courses) - Lớp học cụ thể trong kỳ
CREATE TABLE class_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    semester_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    schedule TEXT,
    room VARCHAR(50),
    current_students INT DEFAULT 0,
    max_students INT DEFAULT 50,
    status ENUM('open', 'full', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    INDEX idx_semester (semester_id),
    INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng đăng ký học phần (enrollments)
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'studying', 'completed', 'dropped') DEFAULT 'registered',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, class_course_id),
    INDEX idx_student (student_id),
    INDEX idx_class_course (class_course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng điểm (grades)
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    assignment_score DECIMAL(4,2) DEFAULT NULL,
    midterm_score DECIMAL(4,2) DEFAULT NULL,
    final_score DECIMAL(4,2) DEFAULT NULL,
    total_score DECIMAL(4,2) DEFAULT NULL,
    letter_grade VARCHAR(5) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade (enrollment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng thông báo (notifications)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT,
    role_target ENUM('all', 'student', 'teacher', 'admin'),
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    is_read ENUM('yes', 'no') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_receiver (receiver_id),
    INDEX idx_role_target (role_target)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng bài tập (assignments)
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_course_id) REFERENCES class_courses(id) ON DELETE CASCADE,
    INDEX idx_class_course (class_course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng nộp bài tập (submissions)
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255),
    content TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score DECIMAL(4,2) DEFAULT NULL,
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_submission (assignment_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DỮ LIỆU MẪU (SAMPLE DATA)
-- =====================================================

-- Thêm quản trị viên mặc định
INSERT INTO users (email, password, role, full_name, phone, address) VALUES
('admin@qlsv.com', 'admin123', 'admin', 'Nguyễn Văn Admin', '0901234567', 'Hà Nội');

-- Thêm giảng viên mẫu
INSERT INTO users (email, password, role, full_name, phone, address) VALUES
('gv1@qlsv.com', 'gv123', 'teacher', 'Trần Thị Lan', '0902345678', 'Hà Nội'),
('gv2@qlsv.com', 'gv123', 'teacher', 'Lê Văn Minh', '0903456789', 'Hà Nội'),
('gv3@qlsv.com', 'gv123', 'teacher', 'Phạm Thị Hoa', '0904567890', 'Hà Nội');

-- Thêm sinh viên mẫu
INSERT INTO users (email, password, role, full_name, phone, address) VALUES
('sv1@qlsv.com', 'sv123', 'student', 'Nguyễn Văn An', '0911111111', 'Hà Nội'),
('sv2@qlsv.com', 'sv123', 'student', 'Trần Thị Bình', '0922222222', 'Hồ Chí Minh'),
('sv3@qlsv.com', 'sv123', 'student', 'Lê Văn Cường', '0933333333', 'Đà Nẵng'),
('sv4@qlsv.com', 'sv123', 'student', 'Phạm Thị Dung', '0944444444', 'Hà Nội'),
('sv5@qlsv.com', 'sv123', 'student', 'Hoàng Văn Em', '0955555555', 'Hải Phòng');

-- Thêm thông tin giảng viên
INSERT INTO teachers (user_id, teacher_code, department, position, specialization) VALUES
(2, 'GV001', 'Khoa Công nghệ Thông tin', 'Giảng viên', 'Lập trình Web'),
(3, 'GV002', 'Khoa Toán - Tin', 'Giảng viên chính', 'Cấu trúc dữ liệu'),
(4, 'GV003', 'Khoa Công nghệ Thông tin', 'Giảng viên', 'Cơ sở dữ liệu');

-- Thêm thông tin sinh viên
INSERT INTO students (user_id, student_code, class, major, enrollment_year, gpa, total_credits) VALUES
(5, 'SV2023001', 'CNTT01-K16', 'Công nghệ Thông tin', 2023, 3.45, 45),
(6, 'SV2023002', 'CNTT01-K16', 'Công nghệ Thông tin', 2023, 3.67, 48),
(7, 'SV2023003', 'CNTT02-K16', 'Công nghệ Thông tin', 2023, 3.12, 42),
(8, 'SV2023004', 'CNTT02-K16', 'Công nghệ Thông tin', 2023, 3.89, 50),
(9, 'SV2023005', 'CNTT01-K16', 'Công nghệ Thông tin', 2023, 3.23, 45);

-- Thêm kỳ học
INSERT INTO semesters (semester_name, semester_code, start_date, end_date, is_active) VALUES
('Học kỳ 1 - Năm học 2023-2024', 'HK1-2023', '2023-09-01', '2024-01-15', 'no'),
('Học kỳ 2 - Năm học 2023-2024', 'HK2-2023', '2024-02-01', '2024-06-30', 'yes'),
('Học kỳ 1 - Năm học 2024-2025', 'HK1-2024', '2024-09-01', '2025-01-15', 'no');

-- Thêm học phần
INSERT INTO courses (course_code, course_name, credits, max_students, description) VALUES
('IT101', 'Lập trình căn bản', 3, 50, 'Học các khái niệm cơ bản về lập trình'),
('IT201', 'Cấu trúc dữ liệu và Giải thuật', 4, 45, 'Học về cấu trúc dữ liệu và các giải thuật cơ bản'),
('IT301', 'Cơ sở dữ liệu', 3, 50, 'Học về thiết kế và quản lý cơ sở dữ liệu'),
('IT302', 'Lập trình Web', 4, 40, 'Học phát triển ứng dụng web'),
('IT401', 'Mạng máy tính', 3, 45, 'Học về mạng máy tính và giao thức'),
('IT402', 'Công nghệ phần mềm', 4, 50, 'Học quy trình phát triển phần mềm');

-- Thêm lớp học phần (kỳ hiện tại)
INSERT INTO class_courses (course_id, teacher_id, semester_id, class_name, schedule, room, current_students, max_students, status) VALUES
(1, 1, 2, 'IT101-01', 'Thứ 2: 7h30-9h30, Thứ 5: 7h30-9h30', 'A101', 15, 50, 'open'),
(2, 2, 2, 'IT201-01', 'Thứ 3: 13h30-16h00, Thứ 6: 13h30-15h00', 'B202', 20, 45, 'open'),
(3, 3, 2, 'IT301-01', 'Thứ 4: 9h30-11h30, Thứ 7: 9h30-11h30', 'C303', 18, 50, 'open'),
(4, 1, 2, 'IT302-01', 'Thứ 2: 13h30-16h00, Thứ 5: 13h30-15h00', 'D404', 22, 40, 'open'),
(5, 2, 2, 'IT401-01', 'Thứ 3: 7h30-9h30, Thứ 6: 7h30-9h30', 'A105', 25, 45, 'open'),
(6, 3, 2, 'IT402-01', 'Thứ 4: 13h30-16h00, Thứ 7: 13h30-15h00', 'B210', 30, 50, 'open');

-- Thêm đăng ký học phần cho sinh viên
INSERT INTO enrollments (student_id, class_course_id, status) VALUES
-- Sinh viên 1
(1, 1, 'studying'),
(1, 3, 'studying'),
(1, 4, 'studying'),
-- Sinh viên 2
(2, 2, 'studying'),
(2, 3, 'studying'),
(2, 5, 'studying'),
-- Sinh viên 3
(3, 1, 'studying'),
(3, 2, 'studying'),
(3, 6, 'studying'),
-- Sinh viên 4
(4, 4, 'studying'),
(4, 5, 'studying'),
(4, 6, 'studying'),
-- Sinh viên 5
(5, 1, 'studying'),
(5, 3, 'studying'),
(5, 5, 'studying');

-- Thêm điểm cho một số sinh viên
INSERT INTO grades (enrollment_id, assignment_score, midterm_score, final_score, total_score, letter_grade) VALUES
(1, 8.5, 7.0, 8.0, 7.83, 'B+'),
(2, 9.0, 8.5, 8.5, 8.67, 'A'),
(3, 7.5, 7.0, 7.5, 7.33, 'B'),
(4, 8.0, 7.5, 8.0, 7.83, 'B+'),
(5, 9.5, 9.0, 9.0, 9.17, 'A+'),
(6, 7.0, 6.5, 7.0, 6.83, 'C+');

-- Thêm thông báo mẫu
INSERT INTO notifications (sender_id, receiver_id, role_target, title, content, is_read) VALUES
(1, NULL, 'all', 'Thông báo về lịch thi cuối kỳ', 'Lịch thi cuối kỳ học kỳ 2 sẽ được công bố vào ngày 15/5/2024. Sinh viên vui lòng theo dõi thường xuyên.', 'no'),
(1, NULL, 'student', 'Thông báo đóng học phí', 'Sinh viên cần hoàn thành đóng học phí trước ngày 30/4/2024 để được dự thi.', 'no'),
(2, 5, 'student', 'Thông báo về bài tập lớn', 'Bài tập lớn môn Lập trình căn bản cần nộp trước ngày 20/5/2024.', 'no');

-- Thêm bài tập mẫu
INSERT INTO assignments (class_course_id, title, description, due_date) VALUES
(1, 'Bài tập 1: Làm quen với Python', 'Viết chương trình in ra màn hình "Hello World" và các phép toán cơ bản', '2024-03-15 23:59:59'),
(1, 'Bài tập 2: Vòng lặp và điều kiện', 'Viết chương trình tính giai thừa và kiểm tra số nguyên tố', '2024-03-22 23:59:59'),
(4, 'Bài tập lớn: Xây dựng website bán hàng', 'Xây dựng website bán hàng đơn giản với HTML, CSS, JavaScript', '2024-05-20 23:59:59');
