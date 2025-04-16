DROP DATABASE IF EXISTS diplomathesis;
CREATE DATABASE diplomathesis;
USE diplomathesis;

CREATE TABLE Student (
    StudentAM INT PRIMARY KEY,
    FullName VARCHAR (100),
    Username VARCHAR(50),
    Email VARCHAR(100),
    MobilePhone VARCHAR(20),
    Phone VARCHAR(20),
    Address VARCHAR(50),
    YearOfEntry DATE,
    Password VARCHAR(25)
);

CREATE TABLE Theses (
    ThesisID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255),
    Description TEXT,
    Link VARCHAR(255),
    STATUS ENUM('Υπό Ανάθεση', 'Υπό Εξέταση', 'Περατωμένη'),
    AssignmentDate DATETIME DEFAULT current_timestamp(),
    StudentAM INT,
    FOREIGN KEY (StudentAM) REFERENCES Student(StudentAM)
);

CREATE TABLE ThesisFiles (
	FileID INT PRIMARY KEY auto_increment,
    ThesisID INT,
    FileType ENUM('Draft', 'Link', 'Other'),
    FilePath varchar(255),
    Description TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
);

CREATE TABLE Examination (
    ThesisID INT PRIMARY KEY,
    ExamDate DATE,
    ExamTime TIME,
    Mode ENUM('Δια ζώσης', 'Διαδικτυακά'),
    Location VARCHAR(25),
    RepositoryLink VARCHAR(255),
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
);
