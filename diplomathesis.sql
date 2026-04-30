DROP DATABASE IF EXISTS diplomathesis;
CREATE DATABASE diplomathesis;
USE diplomathesis;

CREATE TABLE Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Email VARCHAR(100) NOT NULL,
    Password_hash VARCHAR(25) NOT NULL,
    FullName VARCHAR(100) DEFAULT 'unknown' NOT NULL,
    AM INT UNIQUE,                                              #only for students
    UserType ENUM ('Student', 'Professor', 'Secretary') NOT NULL
);

CREATE TABLE Professor (
    ProfessorID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100),
    RegistrationDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
	ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Student (
    StudentID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL, 
    FullName VARCHAR(100) NOT NULL,
    AM INT NOT NULL,
    Email VARCHAR(100),
    MobilePhone VARCHAR(20),
    LandlinePhone VARCHAR(20),
    Address VARCHAR(50),
    YearOfEntry DATE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
	ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Secretary (
    SecretaryID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100),
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
	ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE ThesisTopic (
    TopicID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Summary TEXT,
    PDFpath VARCHAR(255),
    ProfessorID INT NOT NULL,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
	ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE Thesis (
    ThesisID INT PRIMARY KEY AUTO_INCREMENT,
    TopicID INT,
    Title VARCHAR(255),
    ThesisDescription TEXT,
    Link VARCHAR(255),
    ThesisStatus ENUM('Under Assignment', 'Active', 'Cancelled', 'Under Review', 'Completed'),
    AssignmentDate DATETIME DEFAULT CURRENT_TIMESTAMP(),
    StudentID INT,
    SupervisorID INT,
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (SupervisorID) REFERENCES Professor(ProfessorID)
	ON DELETE CASCADE ON UPDATE CASCADE 
);

#Timeline of thesis status
CREATE TABLE ThesisTimeline (
    ThesisID INT,
    ThesisStatus VARCHAR(25),
    ActionDate DATETIME DEFAULT CURRENT_TIMESTAMP(),
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE
);    

#Committee members per thesis
CREATE TABLE ThesisCommittee (
    ThesisID INT,
    ProfessorID INT,
    MemberType ENUM('Supervisor', 'Member'),
    JoinDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ThesisID, ProfessorID),
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

#Committee invitations
CREATE TABLE Invitation (
    InvitationID INT PRIMARY KEY AUTO_INCREMENT,
    ThesisID INT,
    StudentID INT,
    ProfessorID INT,
    InvitationStatus ENUM('Pending', 'Accepted', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    SentAt DATETIME DEFAULT CURRENT_TIMESTAMP(),
    RespondedAt DATETIME,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE InvitationAction (
    ActionID INT PRIMARY KEY AUTO_INCREMENT,
    InvitationID INT NOT NULL, 
    NewStatus ENUM ('Accepted', 'Rejected') NOT NULL,
    ActionAt DATETIME DEFAULT CURRENT_TIMESTAMP(),
    FOREIGN KEY (InvitationID) REFERENCES Invitation(InvitationID)
    ON DELETE CASCADE ON UPDATE CASCADE
);    

#Draft uploads by students
CREATE TABLE ThesisFile (
    FileID INT PRIMARY KEY auto_increment,
    ThesisID INT,
    FileType ENUM('Draft', 'Link', 'Other'),
    FilePath varchar(255),
    Description TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

CREATE TABLE IF NOT EXISTS ThesisAssignmentGA (
  ThesisID   INT PRIMARY KEY,
  GA_Number  INT NOT NULL,
  GA_Year    INT NOT NULL,
  RecordedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  RecordedBy INT NULL,
  CONSTRAINT fk_ga_thesis
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX ix_thesis_ga_year_num ON ThesisAssignmentGA (GA_Year, GA_Number);

#Notes added by professors
CREATE TABLE ThesisNote (
    NoteID INT PRIMARY KEY AUTO_INCREMENT,
    ThesisID INT,
    ProfessorID INT,
    NoteText VARCHAR(300),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

#Grades per committee member
CREATE TABLE ThesisGrade (
    ThesisID INT,
    ProfessorID INT,
    QualityAndGoals DECIMAL(4,2),   -- 60%
    DurationScore DECIMAL(4,2),     -- 15%
    TextCompleteness DECIMAL(4,2),  -- 15%
    PresentationScore DECIMAL(4,2), -- 10%
    FinalScore DECIMAL(4,2),        -- 100%
    PRIMARY KEY (ThesisID, ProfessorID),
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
    ON DELETE CASCADE ON UPDATE CASCADE
);

#Thesis presentation information
CREATE TABLE Presentation (
    ThesisID INT PRIMARY KEY,
    ExamDate DATE,
    ExamTime TIME,
    PresentationType ENUM('InPerson', 'Online'),
    LocationOrLink TEXT,
    AnnouncementText TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE ON UPDATE CASCADE 
);

#Final examination record (HTML)
CREATE TABLE ExaminationRecord (
    ThesisID INT PRIMARY KEY,
    HTMLcontent TEXT,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
);

#Council records for approvals/cancellations
CREATE TABLE CouncilRecord (
    ThesisID INT PRIMARY KEY,
    RecordNumber INT,
    RecordYear INT,
    CancellationReason TEXT,
    CancelledBy ENUM('Student', 'Professor'),
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
);

#Public announcement feed entries
CREATE TABLE PublicAnnouncement (
    AnnouncementID INT PRIMARY KEY AUTO_INCREMENT,
    ThesisID INT,
    PresentationDate DATE,
    AnnouncementFormat ENUM('XML', 'JSON'),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
);

CREATE TABLE ReviewCancellation (
    CancellationID INT PRIMARY KEY AUTO_INCREMENT,
    ThesisID INT NOT NULL,
    Reason VARCHAR(255) NOT NULL DEFAULT 'By the supervisor',
    AssemblyNumber INT NOT NULL,
    AssemblyYear INT NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ThesisID) REFERENCES Thesis(ThesisID)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE enable_grade (
  ThesisID INT NOT NULL PRIMARY KEY,
  Enabled  BOOLEAN NOT NULL DEFAULT TRUE,   -- or DEFAULT FALSE
  CONSTRAINT fk_enable_grade_thesis
    FOREIGN KEY (ThesisID)
    REFERENCES Thesis(ThesisID)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);

#insert the new user's details into the appropriate table for their user type
DELIMITER $
CREATE TRIGGER logUserDetails
AFTER INSERT ON Users
FOR EACH ROW
    BEGIN
        IF (NEW.UserType = 'Professor') THEN
            INSERT INTO Professor (UserID, FullName, Email) 
            VALUES (NEW.UserID, NEW.FullName, NEW.Email);
        ELSEIF (NEW.UserType = 'Student') THEN
            INSERT INTO Student (UserID, FullName, AM, Email) 
            VALUES (NEW.UserID, NEW.FullName, NEW.AM, NEW.Email);
    	ELSE 
            INSERT INTO Secretary (UserID, FullName, Email) 
            VALUES (NEW.UserID, NEW.FullName, NEW.Email);
    	END IF;
	END $
DELIMITER ;
    
INSERT INTO Users (Email, Password_hash, FullName, UserType) VALUES
('gpapado@ac.upatras.gr', 'giorgos123', 'Γιώργος Παπαδόπουλος', 'Professor'),
('mpapado@ac.upatras.gr', 'maria123', 'Μαρία Παπαδοπούλου', 'Professor'),
('gkonst@ac.upatras.gr', 'giorgos2025', 'Γιώργος Κωνσταντίνου', 'Professor'),
('edimi@ac.upatras.gr', 'elenipass', 'Ελένη Δημητρίου', 'Professor'),
('nchristo@ac.upatras.gr', 'nikos$456', 'Νικόλας Χριστόπουλος', 'Professor');

INSERT INTO Users (Email, Password_hash, FullName, AM, UserType) VALUES
('vkosta@ac.upatras.gr', 'vass2024', 'Βασιλική Κώστα', 1100501, 'Student'),
('dantonio@ac.upatras.gr', 'dimi1234', 'Δημήτρης Αντωνίου', 1100502, 'Student'),
('imakri@ac.upatras.gr', 'makripass', 'Ιωάννα Μακρή', 1100503, 'Student'),
('dskord@ac.upatras.gr', 'dimi2004', 'Δήμητρα Σκορδιά', 1100715, 'Student'),
('theomich@ac.upatras.gr', 'theoni2004', 'Θεώνη Μιχαλαρόγιαννη', 1100630, 'Student'),
('gpetrou@ac.upatras.gr', 'geo2025', 'Γεώργιος Πέτρου', 1100510, 'Student'),
('mavram@ac.upatras.gr', 'maria2025', 'Μαρία Αβραμίδου', 1100511, 'Student'),
('kpapas@ac.upatras.gr', 'kostas24', 'Κώστας Παππάς', 1100512, 'Student'),
('etheodorou@ac.upatras.gr', 'efth2024', 'Ευθύμιος Θεοδώρου', 1100513, 'Student'),
('zspano@ac.upatras.gr', 'zoi2025', 'Ζωή Σπανού', 1100514, 'Student');

INSERT INTO Users (Email, Password_hash, FullName, UserType) VALUES
('pkouris@ac.upatras.gr', 'panoscode', 'Παναγιώτης Κουρής', 'Secretary'),
('sspanou@ac.upatras.gr', 'sofia2025', 'Σοφία Σπανού', 'Secretary'),
('calexiou@ac.upatras.gr', 'chriskey', 'Χρήστος Αλεξίου', 'Secretary'),
('oudimi@ac.upatras.gr', 'ourania123', 'Ουρανία Δημητροπούλου', 'Secretary'),
('pdiak@ac.upatras.gr', 'panos123', 'Παναγιώτης Διακουμάκος', 'Secretary');

select * from users;
select * from professor;
select * from student;
select * from secretary;

INSERT INTO ThesisTopic (Title, Summary, PDFpath, ProfessorID) VALUES
('Ανάπτυξη Εφαρμογής Πλοήγησης με Τεχνολογία GPS', 'Μελέτη και υλοποίηση εφαρμογής για Android με χρήση γεωεντοπισμού και χαρτών.', '/files/thesis1.pdf', 1),
('Εξόρυξη Γνώσης από Δεδομένα Υγείας', 'Χρήση τεχνικών data mining σε ηλεκτρονικούς ιατρικούς φακέλους για πρόβλεψη ασθενειών.', '/files/thesis2.pdf', 1),
('Σχεδίαση και Ανάπτυξη Δικτύου IoT για Έξυπνο Σπίτι', 'Δημιουργία συστήματος αισθητήρων και αυτοματισμών για παρακολούθηση και έλεγχο κατοικίας.', '/files/thesis3.pdf', 2),
('Ανάλυση Απόδοσης Αλγορίθμων Ταξινόμησης σε Big Data', 'Πειραματική αξιολόγηση αλγορίθμων σε κατανεμημένα δεδομένα.', '/files/thesis4.pdf', 2),
('Ανάπτυξη Εκπαιδευτικής Πλατφόρμας με Χρήση WebRTC', 'Υλοποίηση πλατφόρμας απομακρυσμένης εκπαίδευσης με δυνατότητες βίντεο και αλληλεπίδρασης.', '/files/thesis5.pdf', 3),
('Ανίχνευση Συναισθήματος σε Κείμενα Μέσων Κοινωνικής Δικτύωσης', 'Εφαρμογή φυσικής γλώσσας για ταξινόμηση συναισθημάτων.', '/files/thesis6.pdf', 3),
('Αξιοποίηση Τεχνητής Νοημοσύνης στην Ανάλυση Εικόνας', 'Ανάπτυξη μοντέλου CNN για αναγνώριση αντικειμένων σε εικόνες.', '/files/thesis7.pdf', 4),
('Μελέτη Αλγορίθμων Κβαντικής Κρυπτογραφίας', 'Θεωρητική προσέγγιση και προσομοίωση βασικών πρωτοκόλλων.', '/files/thesis8.pdf', 4),
('Ανάπτυξη Συστήματος Σύστασης Ταινιών με Χρήση Μηχανικής Μάθησης', 'Συνδυασμός collaborative και content-based filtering.', '/files/thesis9.pdf', 5),
('Ανάλυση Κυβερνοεπιθέσεων και Τεχνικές Ανίχνευσης', 'Κατηγοριοποίηση επιθέσεων και ανάπτυξη μηχανισμών εντοπισμού.', '/files/thesis10.pdf', 5),
('Σχεδίαση RESTful API για Διαχείριση Διπλωματικών', 'Υλοποίηση και τεκμηρίωση REST API (auth, ρόλοι, endpoints, versioning).', '/files/thesis11.pdf', 1),
('Πρόβλεψη Τραυματισμών σε Ποδοσφαιριστές με ML', 'Pipeline μηχανικής μάθησης για πρόβλεψη τραυματισμών με πολλαπλές πηγές δεδομένων.', '/files/thesis12.pdf', 1);

#select * from thesistopic;

DELIMITER $$

DROP TRIGGER IF EXISTS logNewThesisStatus $$
CREATE TRIGGER logNewThesisStatus
AFTER INSERT ON Thesis
FOR EACH ROW
BEGIN
  
  INSERT INTO ThesisTimeline (ThesisID, ThesisStatus) 
  VALUES (NEW.ThesisID, NEW.ThesisStatus);

  IF NEW.TopicID IS NOT NULL
     AND NEW.ThesisStatus IN ('Under Assignment','Active','Under Review','Completed') THEN
    DELETE FROM ThesisTopic
    WHERE TopicID = NEW.TopicID;
  END IF;
END $$

DELIMITER ;


DELIMITER $
CREATE TRIGGER addSupervisorToCommittee
AFTER INSERT ON Thesis
FOR EACH ROW
    BEGIN
        INSERT INTO ThesisCommittee (ThesisID, ProfessorID, MemberType) 
        VALUES (NEW.ThesisID, NEW.SupervisorID, 'Supervisor');
    END $
DELIMITER ;    

INSERT INTO Thesis (TopicID, Title, ThesisDescription, Link, ThesisStatus, StudentID, SupervisorID) VALUES
(1, 'Εφαρμογή Πλοήγησης με GPS', 'Υλοποίηση Android εφαρμογής που εντοπίζει την τοποθεσία του χρήστη και προτείνει διαδρομές.', 'https://thesis.upatras.gr/1', 'Under Assignment', 1, 1),
(2, 'Εξόρυξη Γνώσης από Ιατρικά Δεδομένα', 'Ανάλυση δεδομένων υγείας για την εξαγωγή γνώσης μέσω αλγορίθμων μηχανικής μάθησης.', 'https://thesis.upatras.gr/2', 'Under Assignment', 2, 1),
(3, 'IoT Σύστημα για Έξυπνο Σπίτι', 'Δημιουργία και προγραμματισμός αισθητήρων για την αυτοματοποίηση οικιακού περιβάλλοντος.', 'https://thesis.upatras.gr/3', 'Under Assignment', 3, 2),
(4, 'Αξιολόγηση Αλγορίθμων σε Big Data', 'Σύγκριση αλγορίθμων ταξινόμησης σε κατανεμημένα δεδομένα με χρήση Apache Spark.', 'https://thesis.upatras.gr/4', 'Under Assignment', 4, 2),
(5, 'Εκπαιδευτική Πλατφόρμα με WebRTC', 'Ανάπτυξη σύγχρονης πλατφόρμας τηλεεκπαίδευσης με βιντεοκλήση και διαμοιρασμό υλικού.', 'https://thesis.upatras.gr/5', 'Under Assignment', 5, 3);

INSERT INTO Thesis (TopicID, Title, ThesisDescription, Link, ThesisStatus, StudentID, SupervisorID, AssignmentDate)
VALUES
(11, 'Υλοποίηση RESTful API για Διαχείριση Διπλωματικών', 'Ανάπτυξη, ασφάλεια (JWT), τεκμηρίωση (OpenAPI) και tests για web υπηρεσίες.', 'https://thesis.upatras.gr/11', 'Under Assignment', 6, 1, NULL),
(12, 'Σύστημα Πρόβλεψης Τραυματισμών με ML', 'Συλλογή δεδομένων, feature engineering, εκπαίδευση μοντέλων και αξιολόγηση.', 'https://thesis.upatras.gr/12', 'Active', 10, 1, '2022-01-15');


select * from thesis;

DELIMITER $
CREATE TRIGGER invitationHandler
AFTER INSERT ON InvitationAction
FOR EACH ROW
    BEGIN
        DECLARE thesis_id INT;
        DECLARE accepted_count INT;
        #find invitation's thesis and lock invitation's row
        SELECT ThesisID INTO thesis_id
        FROM Invitation
        WHERE InvitationID = NEW.InvitationID
        FOR UPDATE;
        #update invitation with new status
        UPDATE Invitation
        SET InvitationStatus = NEW.NewStatus, RespondedAt = NEW.ActionAt
        WHERE InvitationID = NEW.InvitationID;
        #how many accepted does this thesis have now
		IF NEW.NewStatus = 'Accepted' THEN
            SELECT COUNT(*) INTO accepted_count
            FROM Invitation
            WHERE ThesisID = thesis_id
            AND InvitationStatus = 'Accepted';
		    #if accepted >= 2, activate thesis and cancel pending invitations
            IF accepted_count >= 2 THEN
                UPDATE Thesis
                SET ThesisStatus = 'Active'
                WHERE ThesisID = thesis_id;
                UPDATE Invitation
                SET InvitationStatus = 'Cancelled'
                WHERE ThesisID = thesis_id AND InvitationStatus = 'Pending';
            END IF;
        END IF;
    END $
DELIMITER ;

INSERT INTO Invitation (ThesisID, StudentID, ProfessorID) VALUES
/*(1, 1, 2),
(1, 1, 3),
(1, 1, 4),
(1, 1, 5),*/
(2, 2, 4),
(2, 2, 5);

#select * from invitation

DELIMITER $
CREATE TRIGGER AddProfessorToCommittee
BEFORE UPDATE ON Invitation
FOR EACH ROW
    BEGIN
        IF (NEW.InvitationStatus <> OLD.InvitationStatus AND NEW.InvitationStatus = 'Accepted') THEN
            INSERT INTO ThesisCommittee (ThesisID, ProfessorID, MemberType) 
            VALUES (NEW.ThesisID, NEW.ProfessorID, 'Member');
		END IF;
	END $
DELIMITER ;

#select * from thesiscommittee;    

#INSERT INTO InvitationAction (InvitationID, NewStatus) 
#VALUES (4, 'Accepted'); 

#select * from invitation;

DELIMITER $$

DELIMITER $$

DROP TRIGGER IF EXISTS logThesisStatusUpdate $$
CREATE TRIGGER logThesisStatusUpdate
AFTER UPDATE ON Thesis
FOR EACH ROW
BEGIN

  IF OLD.ThesisStatus = 'Active'
     AND NEW.ThesisStatus = 'Cancelled'
     AND EXISTS (
          SELECT 1
          FROM ReviewCancellation rc
          WHERE rc.ThesisID = NEW.ThesisID
            AND rc.Reason = 'By the supervisor'
     )
  THEN
    IF TIMESTAMPDIFF(YEAR, COALESCE(OLD.AssignmentDate, NOW()), NOW()) < 2 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot cancel an ACTIVE thesis less than 2 years from AssignmentDate.',
            MYSQL_ERRNO  = 1644; 
    END IF;
  END IF;

  IF NEW.ThesisStatus <> OLD.ThesisStatus AND NEW.ThesisStatus <> 'Cancelled' THEN
    INSERT INTO ThesisTimeline (ThesisID, ThesisStatus)
    VALUES (NEW.ThesisID, NEW.ThesisStatus);

    IF NEW.ThesisStatus = 'Cancelled' THEN
      UPDATE Invitation
      SET InvitationStatus = 'Cancelled'
      WHERE ThesisID = NEW.ThesisID
        AND InvitationStatus IN ('Pending','Accepted');
    END IF;

    IF NEW.TopicID IS NOT NULL
       AND NEW.ThesisStatus IN ('Under Assignment','Active','Under Review','Completed') THEN
      DELETE FROM ThesisTopic
      WHERE TopicID = NEW.TopicID;
    END IF;
  END IF;
END $$

DELIMITER ;


select * from presentation;
/*UPDATE Thesis
SET ThesisStatus = 'Active'
WHERE ThesisID = 4;*/

#select * from thesistimeline;

-- Users
CREATE INDEX idx_users_usertype ON Users (UserType);

-- Professor / Student / Secretary
CREATE INDEX idx_professor_userid ON Professor (UserID);
CREATE INDEX idx_student_userid   ON Student (UserID);
CREATE INDEX idx_secretary_userid ON Secretary (UserID);

-- ThesisTopic
CREATE INDEX idx_thesistopic_professorid ON ThesisTopic (ProfessorID);

-- Thesis
CREATE INDEX idx_thesis_topicid       ON Thesis (TopicID);
CREATE INDEX idx_thesis_studentid     ON Thesis (StudentID);
CREATE INDEX idx_thesis_supervisorid  ON Thesis (SupervisorID);
CREATE INDEX idx_thesis_status        ON Thesis (ThesisStatus);
CREATE INDEX idx_thesis_assignmentdate ON Thesis (AssignmentDate);

-- ThesisTimeline
CREATE INDEX idx_thesistimeline_thesisid        ON ThesisTimeline (ThesisID);
CREATE INDEX idx_thesistimeline_thesisid_date   ON ThesisTimeline (ThesisID, ActionDate);

-- ThesisCommittee
CREATE INDEX idx_thesiscommittee_professorid ON ThesisCommittee (ProfessorID);

-- Invitation
CREATE INDEX idx_invitation_thesisid     ON Invitation (ThesisID);
CREATE INDEX idx_invitation_professorid  ON Invitation (ProfessorID);
CREATE INDEX idx_invitation_studentid    ON Invitation (StudentID);
CREATE INDEX idx_invitation_status       ON Invitation (InvitationStatus);
CREATE INDEX idx_invitation_sentat       ON Invitation (SentAt);

-- InvitationAction
CREATE INDEX idx_invitationaction_invitationid ON InvitationAction (InvitationID);
CREATE INDEX idx_invitationaction_actionat     ON InvitationAction (ActionAt);

-- ThesisFile
CREATE INDEX idx_thesisfile_thesisid ON ThesisFile (ThesisID);
CREATE INDEX idx_thesisfile_filetype ON ThesisFile (FileType);

-- ThesisNote
CREATE INDEX idx_thesisnote_thesisid    ON ThesisNote (ThesisID);
CREATE INDEX idx_thesisnote_professorid ON ThesisNote (ProfessorID);
CREATE INDEX idx_thesisnote_createdat   ON ThesisNote (CreatedAt);

-- ThesisGrade
CREATE INDEX idx_thesisgrade_professorid ON ThesisGrade (ProfessorID);

-- Presentation
CREATE INDEX idx_presentation_examdate ON Presentation (ExamDate);

-- CouncilRecord
CREATE INDEX idx_councilrecord_recordyear  ON CouncilRecord (RecordYear);
CREATE INDEX idx_councilrecord_recordnumber ON CouncilRecord (RecordNumber);

-- PublicAnnouncement
CREATE INDEX idx_publicannouncement_thesisid        ON PublicAnnouncement (ThesisID);
CREATE INDEX idx_publicannouncement_presentationdate ON PublicAnnouncement (PresentationDate);
CREATE INDEX idx_publicannouncement_format          ON PublicAnnouncement (AnnouncementFormat);

-- ReviewCancellation
CREATE INDEX idx_reviewcancellation_thesisid  ON ReviewCancellation (ThesisID);
CREATE INDEX idx_reviewcancellation_assembly  ON ReviewCancellation (AssemblyYear, AssemblyNumber);

-- enable_grade (προαιρετικό, αν φιλτράρεις συχνά από Enabled)
CREATE INDEX idx_enablegrade_enabled ON enable_grade (Enabled);
/*
INSERT INTO Invitation (ThesisID, StudentID, ProfessorID) VALUES
(6, 6, 2),
(6, 6, 4);

INSERT INTO Invitation (ThesisID, StudentID, ProfessorID) VALUES (6, 6, 3);*/

#added this for secretary json file
ALTER TABLE Professor
ADD COLUMN Name VARCHAR(100) DEFAULT NULL,
ADD COLUMN Surname VARCHAR(100) DEFAULT NULL,
ADD COLUMN Topic VARCHAR(255) DEFAULT NULL,
ADD COLUMN Landline VARCHAR(20) DEFAULT NULL,
ADD COLUMN Mobile VARCHAR(20) DEFAULT NULL,
ADD COLUMN Department VARCHAR(100) DEFAULT NULL,
ADD COLUMN University VARCHAR(100) DEFAULT NULL,
ADD COLUMN id VARCHAR(20) DEFAULT NULL;

ALTER TABLE Student
ADD COLUMN Name VARCHAR(100) DEFAULT NULL,
ADD COLUMN Surname VARCHAR(100) DEFAULT NULL,
ADD COLUMN Street VARCHAR(100) DEFAULT NULL,
ADD COLUMN Number VARCHAR(10) DEFAULT NULL,
ADD COLUMN City VARCHAR(100) DEFAULT NULL,
ADD COLUMN Postcode VARCHAR(20) DEFAULT NULL,
ADD COLUMN Father_Name VARCHAR(100) DEFAULT NULL,
ADD COLUMN id VARCHAR(20) DEFAULT NULL;

ALTER TABLE Professor 
MODIFY FullName VARCHAR(100) NULL;

ALTER TABLE Student
MODIFY FullName VARCHAR(100) NULL;

SHOW CREATE TABLE Professor;
SHOW CREATE TABLE Student;

ALTER TABLE Users
MODIFY Password_hash VARCHAR(255) NOT NULL DEFAULT '123';
ALTER TABLE Users
ADD COLUMN ExternalID VARCHAR(50) DEFAULT NULL;

select * from invitationaction;
select * from thesiscommittee;



INSERT INTO Presentation
(ThesisID, ExamDate,   ExamTime,  PresentationType, LocationOrLink, 
 AnnouncementText)
VALUES

(1, '2025-10-03', '12:00:00', 'InPerson',
 'Αίθουσα Σεμιναρίων Κτιρίου Β, Τμήμα Πληροφορικής',
 'Δημόσια παρουσίαση διπλωματικής. Παρακαλούνται οι φοιτητές/τριες να προσέλθουν 10’ νωρίτερα.'),


(2, '2025-10-10', '11:30:00', 'Online',
 'https://upatras-gr.zoom.us/j/1234567890',
 'Η παρουσίαση θα γίνει μέσω Zoom. Χρησιμοποιήστε τον παραπάνω σύνδεσμο.'),


(3, '2025-10-17', '13:00:00', 'InPerson',
 'Αίθουσα 3.2, Κεντρικό Κτίριο',
 'Η επιτροπή θα ακολουθήσει με σύντομες ερωτήσεις μετά το πέρας της ομιλίας.'),


(5, '2025-10-24', '09:30:00', 'Online',
 'https://meet.google.com/abc-defg-hij',
 'Παρακαλούνται οι παρευρισκόμενοι να συνδεθούν 5’ νωρίτερα για έλεγχο ήχου/εικόνας.');

