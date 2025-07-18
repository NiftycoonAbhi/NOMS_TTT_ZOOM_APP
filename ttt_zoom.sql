-- Table to store the overall meeting metadata
CREATE TABLE meeting_att_head (
  meeting_id VARCHAR(50) PRIMARY KEY,
  meeting_date DATE,
  start_time DATETIME,
  end_time DATETIME
);

-- Table to store individual participant attendance records
CREATE TABLE meeting_att_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id VARCHAR(50),
  student_id VARCHAR(50),
  meeting_date DATE,
  join_time DATETIME,
  leave_time DATETIME
);

-- Index to speed up queries on head table by meeting_id
CREATE INDEX idx_meeting_id ON meeting_att_head(meeting_id);

-- ✅ CORRECTION: "start_time" is not in meeting_att_details
-- Fix: remove it from the index and keep only relevant columns
CREATE INDEX idx_meeting_student 
ON meeting_att_details(meeting_id, meeting_date, student_id);
