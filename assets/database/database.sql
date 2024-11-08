-- Students Table
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    reg_number VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_registered BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Index for frequent searches by reg number, email, and registration status
CREATE INDEX idx_students_reg_number ON students(reg_number);
CREATE INDEX idx_students_email ON students(email);
CREATE INDEX idx_students_reg_status ON students(is_registered);
-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);
-- Index for frequent searches by student_id
CREATE INDEX idx_users_student_id ON users(student_id);
-- Admins Table
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'DoSA', 'MEC') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Indexes for frequent searches by email and role
CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_admins_role ON admins(role);
-- Elections Table
CREATE TABLE elections (
    election_id INT PRIMARY KEY AUTO_INCREMENT,
    election_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    election_status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Positions Table
CREATE TABLE positions (
    position_id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    position_name VARCHAR(50) NOT NULL,
    position_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(election_id) ON DELETE CASCADE
);
-- Index for frequent queries on election_id
CREATE INDEX idx_positions_election_id ON positions(election_id);
-- Candidates Table
CREATE TABLE candidates (
    candidate_id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    image_url VARCHAR(100),
    manifesto TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(election_id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE
);
-- Index for frequent queries on election_id and position_id
CREATE INDEX idx_candidates_election_id ON candidates(election_id);
CREATE INDEX idx_candidates_position_id ON candidates(position_id);
-- Votes Table
CREATE TABLE votes (
    vote_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    vote_hash CHAR(64) NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
    FOREIGN KEY (election_id) REFERENCES elections(election_id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE,
    CONSTRAINT unique_student_election_position UNIQUE (user_id, election_id, position_id)
);
-- Indexes for frequent queries on user_id, candidate_id, election_id, position_id, and vote_id
CREATE INDEX idx_votes_user_id ON votes(user_id);
CREATE INDEX idx_votes_candidate_id ON votes(candidate_id);
CREATE INDEX idx_votes_election_id ON votes(election_id);
CREATE INDEX idx_votes_position_id ON votes(position_id);
CREATE INDEX idx_votes_vote_hash ON votes(vote_hash);
CREATE INDEX idx_votes_vote_id ON votes(vote_id);
-- Login Audit Table
CREATE TABLE login_audit (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    attempted_account VARCHAR(100) NOT NULL,
    account_type ENUM('student', 'admin', 'unknown') NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(39),
    client VARCHAR(255),
    login_status ENUM('successful', 'failed') NOT NULL,
    student_id INT,
    admin_id INT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
);
-- Indexes for quicker filtering by account_type, login_status, student_id, and admin_id
CREATE INDEX idx_login_audit_account_type ON login_audit(account_type);
CREATE INDEX idx_login_audit_status ON login_audit(login_status);
CREATE INDEX idx_login_audit_student_id ON login_audit(student_id);
CREATE INDEX idx_login_audit_admin_id ON login_audit(admin_id);
-- Vote Audit Table
CREATE TABLE vote_audit (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    vote_id INT NOT NULL,
    user_id INT NOT NULL,
    candidate_id INT NOT NULL,
    election_id INT NOT NULL,
    position_id INT NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(vote_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
    FOREIGN KEY (election_id) REFERENCES elections(election_id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE CASCADE
);
-- Index for tracking voting activity more efficiently
CREATE INDEX idx_vote_audit_user_id ON vote_audit(user_id);
CREATE INDEX idx_vote_audit_election_id ON vote_audit(election_id);
-- Trigger for auto audit votes
DELIMITER // CREATE TRIGGER after_vote_insert
AFTER
INSERT ON votes FOR EACH ROW BEGIN
INSERT INTO vote_audit (
        vote_id,
        user_id,
        candidate_id,
        election_id,
        position_id,
        vote_time
    )
VALUES (
        NEW.vote_id,
        NEW.user_id,
        NEW.candidate_id,
        NEW.election_id,
        NEW.position_id,
        NEW.vote_time
    );
END // DELIMITER