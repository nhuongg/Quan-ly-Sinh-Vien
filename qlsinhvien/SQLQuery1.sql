-- Tạo cơ sở dữ liệu
CREATE DATABASE QL_SINH_VIEN;
GO

USE QL_SINH_VIEN;
GO

-- Bảng Sinh viên
CREATE TABLE SinhVien (
    MaSV NVARCHAR(10) PRIMARY KEY,
    Ho NVARCHAR(50),
    Ten NVARCHAR(50),
    GioiTinh NVARCHAR(10),
    NgaySinh DATE,
    DiaChi NVARCHAR(200),
    SDT NVARCHAR(15)
);
GO

-- Bảng Lớp
CREATE TABLE Lop (
    MaLop NVARCHAR(10) PRIMARY KEY,

    TenLop NVARCHAR(50),
    CoVan NVARCHAR(50)
);
GO

-- Bảng Môn học
CREATE TABLE MonHoc (
    MaMH NVARCHAR(10) PRIMARY KEY,
    TenMH NVARCHAR(100),
    SoTinChi INT
);
GO

-- Bảng Điểm
CREATE TABLE Diem (
    MaDiem INT IDENTITY(1,1) PRIMARY KEY,
    MaSV NVARCHAR(10),
    MaMH NVARCHAR(10),
    HocKy NVARCHAR(10),
    DiemGiuaKy FLOAT,
    DiemCuoiKy FLOAT,
    DiemTrungBinh AS (DiemGiuaKy * 0.4 + DiemCuoiKy * 0.6),
    CONSTRAINT FK_Diem_SinhVien FOREIGN KEY (MaSV) REFERENCES SinhVien(MaSV),
    CONSTRAINT FK_Diem_MonHoc FOREIGN KEY (MaMH) REFERENCES MonHoc(MaMH)
);
GO

-- Bảng Phân lớp
CREATE TABLE PhanLop (
    MaSV NVARCHAR(10),
    MaLop NVARCHAR(10),
    NamHoc NVARCHAR(9),
    PRIMARY KEY (MaSV, MaLop, NamHoc),
    CONSTRAINT FK_PhanLop_SinhVien FOREIGN KEY (MaSV) REFERENCES SinhVien(MaSV),
    CONSTRAINT FK_PhanLop_Lop FOREIGN KEY (MaLop) REFERENCES Lop(MaLop)
);
GO

-- Dữ liệu mẫu
INSERT INTO SinhVien VALUES
(N'SV001', N'Nguyễn', N'An', N'Nam', '2004-09-15', N'Hà Nội', N'0901123456'),
(N'SV002', N'Trần', N'Bình', N'Nam', '2003-07-10', N'Hải Phòng', N'0901987654'),
(N'SV003', N'Lê', N'Cẩm', N'Nữ', '2004-11-20', N'Hà Nội', N'0901777888');
GO

INSERT INTO Lop VALUES
(N'DHTH01', N'Công nghệ thông tin 1', N'Trần Văn Hùng'),
(N'DHTH02', N'Công nghệ thông tin 2', N'Lê Thị Hạnh');
GO

INSERT INTO MonHoc VALUES
(N'MH001', N'Cơ sở dữ liệu', 3),
(N'MH002', N'Lập trình Python', 3),
(N'MH003', N'Trí tuệ nhân tạo', 2);
GO

INSERT INTO PhanLop VALUES
(N'SV001', N'DHTH01', N'2025-2026'),
(N'SV002', N'DHTH01', N'2025-2026'),
(N'SV003', N'DHTH02', N'2025-2026');
GO

INSERT INTO Diem (MaSV, MaMH, HocKy, DiemGiuaKy, DiemCuoiKy) VALUES
(N'SV001', N'MH001', N'HK1', 7.5, 8.0),
(N'SV001', N'MH002', N'HK1', 6.5, 7.0),
(N'SV002', N'MH001', N'HK1', 8.5, 8.5),
(N'SV003', N'MH003', N'HK1', 9.0, 9.5);
GO

-- VIEW: Bảng điểm sinh viên
CREATE VIEW v_BangDiem AS
SELECT 
    sv.MaSV,
    (sv.Ho + ' ' + sv.Ten) AS HoTen,
    mh.TenMH,
    d.HocKy,
    d.DiemGiuaKy,
    d.DiemCuoiKy,
    d.DiemTrungBinh
FROM Diem d
JOIN SinhVien sv ON d.MaSV = sv.MaSV
JOIN MonHoc mh ON d.MaMH = mh.MaMH;
GO

-- THỦ TỤC: Xem bảng điểm của một sinh viên
CREATE PROCEDURE sp_XemBangDiem @MaSV NVARCHAR(10)
AS
BEGIN
    SELECT 
        sv.MaSV,
        (sv.Ho + ' ' + sv.Ten) AS HoTen,
        mh.TenMH,
        d.HocKy,
        d.DiemGiuaKy,
        d.DiemCuoiKy,
        d.DiemTrungBinh
    FROM Diem d
    JOIN SinhVien sv ON d.MaSV = sv.MaSV
    JOIN MonHoc mh ON d.MaMH = mh.MaMH
    WHERE sv.MaSV = @MaSV;
END;
GO
