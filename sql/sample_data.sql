-- Sample data for testing
USE if0_39877333_dog_adoption;

-- Insert sample users
INSERT INTO users (username, email, password_hash, phone_number, role) VALUES
('admin', 'admin@pawsome.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123-456-7890', 'admin'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123-456-7891', 'user'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123-456-7892', 'user');

-- Insert sample dogs
INSERT INTO dogs (name, breed, age, gender, size, description, image_path, status) VALUES
('Buddy', 'Golden Retriever', 3, 'male', 'large', 'Friendly and energetic golden retriever who loves playing fetch and going for long walks. Great with children and other pets.', 'images/dog1.jpg', 'available'),
('Luna', 'Labrador Mix', 2, 'female', 'medium', 'Sweet and gentle lab mix looking for a loving home. She is house-trained and knows basic commands.', 'images/dog2.jpg', 'available'),
('Max', 'German Shepherd', 4, 'male', 'large', 'Loyal and protective German Shepherd. Requires an experienced owner who can provide proper training and exercise.', 'images/dog3.jpg', 'available'),
('Bella', 'Beagle', 1, 'female', 'small', 'Playful young beagle full of energy. Loves exploring and would do well in an active household.', 'images/dog4.jpg', 'available'),
('Charlie', 'Poodle', 5, 'male', 'medium', 'Smart and affectionate poodle who enjoys cuddles and learning new tricks. Hypoallergenic coat.', 'images/dog5.jpg', 'available'),
('Daisy', 'Bulldog', 3, 'female', 'medium', 'Calm and gentle bulldog who prefers relaxing over vigorous exercise. Great companion for apartment living.', 'images/dog6.jpg', 'available');