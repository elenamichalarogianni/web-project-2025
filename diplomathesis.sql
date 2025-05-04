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
    SPassword VARCHAR(25)
);

CREATE TABLE Secretary  (
    SecretaryAM INT PRIMARY KEY,
    FullName VARCHAR (100),
    Username VARCHAR(50),
    Email VARCHAR(100),
    MobilePhone VARCHAR(20),
    Phone VARCHAR(20),
    Address VARCHAR(50),
    SecPassword VARCHAR(25)
);

CREATE TABLE Professor (
    ProfessorID INT PRIMARY KEY AUTO_INCREMENT,
    FullName VARCHAR(100),
    Email VARCHAR(100)
);


CREATE TABLE Theses (
    ThesisID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255),
    Descr TEXT,
    Link VARCHAR(255),
    thesisstatus ENUM('Under Assignment','Under Review', 'Completed'),
    actstatus ENUM('Active','Inactive') default 'Inactive',
    AssignmentDate DATETIME DEFAULT current_timestamp(),
    InsertGrade ENUM('Yes','No') DEFAULT 'No',
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
    MemberType ENUM('Supervisor','Member'),
    MemberStatus ENUM('Invited','Accepted','Rejected') DEFAULT 'Invited',
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
    FilePath TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE Presentation (
    ThesisID INT PRIMARY KEY,
    ExamDate DATE,
    ExamTime TIME,
    ExamMode ENUM('Live', 'online'),
    Location VARCHAR(25) ,
    RepositoryLink VARCHAR(255) ,
    FOREIGN KEY (ThesisID) REFERENCES Theses(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

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
    
        INSERT INTO ThesisCommittee (ThesisID, ProfessorID, membertype, memberStatus)
        VALUES (inthesisID, inprofessorID, inrole, 'Invited');
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invitation already sent';
    END IF;
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE ActivateThesis(
     IN inThesisID INT
)
BEGIN
    DECLARE acceptedCount INT;

    SELECT COUNT(*) INTO acceptedCount
    FROM ThesisCommittee
    WHERE ThesisID = inThesisID AND memberStatus = 'Accepted';

    IF acceptedCount >= 2 THEN
        UPDATE Theses
        SET ACTSTATUS = 'Active'
        WHERE ThesisID = inThesisID;


        UPDATE ThesisCommittee
        SET memberStatus = 'Rejected'
        WHERE ThesisID = inThesisID AND memberStatus = 'Invited';
        
        DELETE FROM ThesisCommittee
        WHERE ThesisID = inThesisID AND memberStatus = 'Rejected';
    END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER check_presentation_validity
BEFORE INSERT ON Presentation
FOR EACH ROW
BEGIN
    DECLARE thesis_status ENUM('Under Assignment','Under Review','Completed');

    
    SELECT thesisStatus INTO thesis_status
    FROM Theses
    WHERE ThesisID = NEW.ThesisID;


    IF thesis_status <> 'Under Review' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Thesis is not "Under Review"';
    END IF;

    
    IF NEW.ExamMode = 'Live' THEN
        IF NEW.Location IS NULL OR NEW.Location REGEXP '^(http|https)://' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Please insert room for live presentation.';
        END IF;
        IF NEW.RepositoryLink IS NOT NULL AND LENGTH(TRIM(NEW.RepositoryLink)) > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No link allowed for live presentation';
        END IF;
    END IF;

   
    IF NEW.ExamMode = 'online' THEN
        IF NEW.RepositoryLink IS NULL OR NEW.RepositoryLink NOT REGEXP '^(http|https)://' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'For online presentation please insert link';
        END IF;
        IF NEW.Location IS NOT NULL AND NEW.Location REGEXP '^(http|https)://' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No link allowed for live presentation';
        END IF;
    END IF;

END$$

DELIMITER ;

#TESTING

INSERT INTO Student (StudentAM, FullName, Username, Email, MobilePhone, Phone, Address, YearOfEntry, SPassword)
VALUES (2023001, 'Φρώσω Παπαδοπούλου', 'froso_pap', 'frosopap@example.com', '6912345678', '2101234567', 'Αθήνα 1', '2023-10-01', 'pass123');


INSERT INTO Professor (FullName, Email) VALUES
('Δρ. Γεώργιος Νικολάου', 'gnik@example.com'),
('Δρ. Ελένη Παναγιώτου', 'epan@example.com');

INSERT INTO Theses (Title, Descr, Link, thesisStatus, StudentAM)
VALUES ('Ανάπτυξη Web Εφαρμογής', 'Περιγραφή διπλωματικής', 'link.pdf', 'Under Review',2023001);

CALL SendInvitation(1, 1, 'Member');
CALL SendInvitation(1, 2, 'Supervisor');

UPDATE ThesisCommittee SET memberStatus = 'Accepted' WHERE ThesisID = 1 AND ProfessorID = 1;
UPDATE ThesisCommittee SET memberStatus = 'Accepted' WHERE ThesisID = 1 AND ProfessorID = 2;

SELECT * FROM ThesisCommittee;
SELECT * FROM THESES;
CALL ActivateThesis(1);

SELECT actStatus FROM Theses WHERE ThesisID = 1;
SELECT * FROM THESES;
SELECT * FROM ThesisCommittee WHERE ThesisID = 1;

INSERT INTO Theses (Title, Descr, thesisStatus, StudentAM)
VALUES ('Test Thesis Online Missing Link', 'Dummy', 'Under Review', 2023001);

INSERT INTO Presentation (ThesisID, ExamDate, ExamTime, ExamMode, Location, RepositoryLink)
VALUES (2, '2025-06-01', '12:00:00', 'online', null, 'https://ceid');



