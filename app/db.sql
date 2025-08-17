CREATE DATABASE IF NOT EXISTS metronome_manual CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE metronome_manual;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  user_id VARCHAR(128) NOT NULL,
  offset_usec BIGINT NOT NULL,
  -- The timestamp when either Server or Client started their settings metronome.
  timestamp_usec BIGINT NULL,
  is_ref BOOLEAN NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);