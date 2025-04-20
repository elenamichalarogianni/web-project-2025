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

CREATE TABLE Professor (
    ProfessorID INT PRIMARY KEY AUTO_INCREMENT,
    FullName VARCHAR(100),
    Email VARCHAR(100)
);


CREATE TABLE Theses (
    ThesisID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255),
    Description TEXT,
    Link VARCHAR(255),
    STATUS ENUM('Under Assignment', 'Under Review', 'Completed'),
    AssignmentDate DATETIME DEFAULT current_timestamp(),
    StudentAM INT,
    SupervisorID INT,
    FOREIGN KEY (StudentAM) REFERENCES Student(StudentAM)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (SupervisorID) REFERENCES Professor(ProfessorID)
     ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE ThesisCommittee (
    ThesisID INT,
    ProfessorID INT,
    Role ENUM('Supervisor','Member'),
    Status ENUM('Invited','Accepted','Rejected') DEFAULT 'Invited',
    PRIMARY KEY (ThesisID, ProfessorID),
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE ThesisFiles (
	FileID INT PRIMARY KEY auto_increment,
    ThesisID INT,
    FileType ENUM('Draft', 'Link', 'Other'),
    FilePath varchar(255),
    Description TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE Examination (
    ThesisID INT PRIMARY KEY,
    ExamDate DATE,
    ExamTime TIME,
    Mode ENUM('Live', 'online'),
    Location VARCHAR(25),
    RepositoryLink VARCHAR(255),
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

Insert into Profess

DELIMITER $$

CREATE PROCEDURE SendInvitation(
    IN inthesisID INT,
    IN inprofessorID INT,
    IN inrole ENUM('Supervisor', 'Member')
)
BEGIN

    IF NOT EXISTS (
        SELECT * FROM ThesisCommittee
        WHERE ThesisID = inthesisID AND ProfessorID = inprofessorID
    ) THEN
    
        INSERT INTO ThesisCommittee (ThesisID, ProfessorID, Role, Status)
        VALUES (inthesisID, inprofessorID, inrole, 'Invited');
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Πρόσκληση έχει ήδη σταλεί σε αυτόν τον καθηγητή για αυτή τη διπλωματική.';
    END IF;
END$$

DELIMITER ;

INSERT INTO Student (StudentAM, FullName, Username, Email, MobilePhone, Phone, Address, YearOfEntry, Password)
VALUES (2023001, 'Μαρία Παπαδοπούλου', 'maria_pap', 'maria@example.com', '6912345678', '2101234567', 'Αθήνα 1', '2023-10-01', 'pass123');


INSERT INTO Professor (FullName, Email) VALUES
('Δρ. Γεώργιος Νικολάου', 'gnik@example.com'),
('Δρ. Ελένη Παναγιώτου', 'epan@example.com');

INSERT INTO Theses (Title, Description, Link, Status, StudentAM)
VALUES ('Ανάπτυξη Web Εφαρμογής', 'Περιγραφή διπλωματικής', 'link.pdf', 'Under Review', 2023001);

CALL SendInvitation(1, 1, 'Member');
CALL SendInvitation(1, 2, 'Supervisor');
