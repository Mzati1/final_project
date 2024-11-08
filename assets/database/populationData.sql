-- Insert Students Data
INSERT INTO students (
        first_name,
        last_name,
        reg_number,
        email,
        is_registered
    )
VALUES (
        'Mzati',
        'Tembo',
        'CSS-108-22',
        'css-108-22@must.ac.mw',
        TRUE
    ),
    (
        'John',
        'Doe',
        'CSS-109-23',
        'john.doe@must.ac.mw',
        TRUE
    ),
    (
        'Jane',
        'Smith',
        'CSS-110-23',
        'jane.smith@must.ac.mw',
        TRUE
    ),
    (
        'Michael',
        'Johnson',
        'CSS-111-23',
        'michael.johnson@must.ac.mw',
        TRUE
    ),
    (
        'Emily',
        'Davis',
        'CSS-112-23',
        'emily.davis@must.ac.mw',
        TRUE
    ),
    (
        'William',
        'Brown',
        'CSS-113-23',
        'william.brown@must.ac.mw',
        TRUE
    );
-- Insert Elections Data
INSERT INTO elections (
        election_name,
        start_date,
        end_date,
        election_status
    )
VALUES (
        'Student President Election',
        '2024-11-01',
        '2024-11-05',
        'open'
    ),
    (
        'Vice President Election',
        '2024-11-01',
        '2024-11-05',
        'open'
    );
-- Insert Positions Data
INSERT INTO positions (election_id, position_name, position_description)
VALUES (
        1,
        'President',
        'The president of the student body.'
    ),
    (
        1,
        'Vice President',
        'The vice president of the student body.'
    ),
    (
        1,
        'Secretary',
        'The secretary of the student body.'
    ),
    (
        2,
        'Vice President',
        'The vice president of the student body.'
    );
-- Insert Candidates Data
INSERT INTO candidates (
        election_id,
        position_id,
        first_name,
        last_name,
        image_url,
        manifesto
    )
VALUES (
        1,
        1,
        'Chris',
        'Williams',
        'https://example.com/image1.jpg',
        'I promise to work hard for students.'
    ),
    (
        1,
        2,
        'Patricia',
        'Garcia',
        'https://example.com/image2.jpg',
        'I will serve students with honesty and integrity.'
    ),
    (
        1,
        3,
        'David',
        'Martinez',
        'https://example.com/image3.jpg',
        'I will ensure transparency in student activities.'
    ),
    (
        2,
        1,
        'Linda',
        'Lopez',
        'https://example.com/image4.jpg',
        'I will create initiatives for student welfare.'
    ),
    (
        2,
        2,
        'Jake',
        'Roberts',
        'https://example.com/image5.jpg',
        'I will lead with a focus on student needs.'
    );