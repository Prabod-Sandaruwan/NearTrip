-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Sep 07, 2026 at 03:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `neartrip`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`admin_id`, `username`, `password_hash`, `email`, `role`, `created_at`) VALUES
(6, 'admin', '$2y$10$8BlnSBFtyON4vBk2x4r84eXtPP8BTD9DYQ63vu3M2af1KDlRKR/Ne', 'admin@neartrip.com', 'admin', '2026-09-05 19:02:28'),
(7, 'user', '$2y$10$dKHd0TXvkGVRdn0bf7JO/uTq1hWBJDxDS111cZtkeIHPjEW1GDXE.', 'user@neartrip.com', 'user', '2026-09-05 19:02:28'),
(12, 'sandaruwan', '$2y$10$xC4cmd90OVc/y2m7jokO1uuJ1tfrU6ERFyK2Ce381cuPu9sPcyyw6', 'sandaru@neartrip.com', 'user', '2026-09-06 14:11:49');

-- --------------------------------------------------------

--
-- Table structure for table `attraction`
--

CREATE TABLE `attraction` (
  `attraction_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'GPS latitude coordinate',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'GPS longitude coordinate',
  `opening_hours` varchar(100) DEFAULT NULL COMMENT 'Opening hours of the attraction',
  `entrance_fee` varchar(100) DEFAULT NULL COMMENT 'Entrance fee (can be "Free" or amount)',
  `facilities` text DEFAULT NULL COMMENT 'Available facilities (parking, restrooms, etc.)',
  `travel_tips` text DEFAULT NULL COMMENT 'Travel tips for visitors',
  `safety_guidelines` text DEFAULT NULL COMMENT 'Safety guidelines for visitors',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'Path to attraction image'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attraction`
--

INSERT INTO `attraction` (`attraction_id`, `category_id`, `admin_id`, `name`, `description`, `location`, `latitude`, `longitude`, `opening_hours`, `entrance_fee`, `facilities`, `travel_tips`, `safety_guidelines`, `image_url`) VALUES
(2, 5, NULL, 'Dunumala Waterfall (Dunumala Ella)', 'Dunumala Ella, also known as Uduwaka Ella, Alawala Devin Panna Ella, or Devin Penna Ella, is a picturesque waterfall situated in Uduwaka village on the border of the Gampaha and Kegalle districts. Formed by the Algama Oya—a tributary of the Attanagalu Oya—this stunning waterfall cascades in three distinct sections down a rocky cliff, settling beautifully into a small natural pool. Beyond its natural charm, the surrounding area holds significant historical and archaeological value; nearby caves are believed to have sheltered King Vijayabahu III, while a unique rock formation near the falls features five holes historically used by ancient queens to play the traditional game of Pancha. Adding to its cultural allure, this captivating cascade famously served as the cinematic backdrop for scenes in the renowned 2013 Sinhala historical movie, \"Siri Perakum\".', 'Uduwaka village, Dunumala, Warakapola, Kegalle District, Sri Lanka', 7.14808243, 80.20169533, 'Open 24 hours Daily', 'Free entry', 'Parking available (private properties), Local food shops (Roti, Hoppers, Pancakes), Changing facilities available at nearby shops', 'For the best experience, visit during weekdays to avoid crowds and plan your trip during the rainy season to enjoy maximum water flow. The location is highly suitable for family outings and photography, though it involves a short hike of approximately 500m from the main road. Be sure to wear comfortable shoes with good grip and remember to carry water and snacks. Additionally, please drive carefully as the access road is narrow.', 'Please exercise caution as the rocks are very slippery when walking, and watch where you step due to the presence of broken glass bottles. Avoid visiting during heavy rain, as water levels can rise quickly. To protect the environment, take all trash back with you and ensure you do not leave any plastic or food waste behind.', '/assets/dunumala.webp'),
(3, 1, NULL, 'Thalagama Raja Maha Viharaya', 'Built on a mountain top by King Walagamba, this ancient cave temple is historically significant as it is believed to be the only temple in Sri Lanka that replicates the famous Sanchi Stupa of India. The sacred complex is beautifully structured across three levels: the lower level houses the Sangawasaya and Dharma Shalawa, the second level contains the Vihara house and the sacred Bodhi tree, while the third level features a Gal kema (rock pond) alongside an ancient stone inscription (sel lipi). Adding to its immense archaeological value, a Brahmi stone inscription found at the site—studied by Professor Paranawithana—dates back to the 2nd to 7th centuries and historically records the release of slaves.', 'Thalagama, Warakapola, Kegalle District, Sri Lanka', 7.25022436, 80.18155796, '6:00 AM - 7:00 PM Daily', 'Free entry', 'Parking available, Walking paths, Seating areas, Rock pond (Gal kema)', 'For the best experience, plan your visit during the morning or evening and remember to carry water. This destination is highly suitable for history and culture enthusiasts, and taking a guide is recommended for deeper historical insights.', 'Please be careful when navigating stepped paths and rocky surfaces, and avoid climbing on any ancient structures. Visitors must follow all temple rules, respect local religious customs, and note that photography is not permitted inside the shrine areas.', '/assets/thalagama.webp'),
(4, 2, NULL, 'Dedigama Kota Vehera (Sutighara Cetiya) & Archaeological Museum', 'Dedigama Kota Vehera—also known as Suthigara Cetiya or Suthigara Dagoba—is a massive ancient stupa built by King Parakramabahu the Great (1153-1186 AD) to commemorate his birthplace. This historical monument is uniquely characterized by its incomplete appearance, as it lacks the upper turret (kota), which ultimately gives it the name \"Kota Vehera.\" The structure brilliantly consists of a smaller inner stupa with a 36-foot diameter, built directly over the exact spot of the king\'s birth, which is then completely surrounded by a larger stupa. Adjacent to this site is the Dedigama Archaeological Museum; established in 1954 as the first regional museum of the Archaeological Department, it proudly displays antiquities discovered during the excavation of the stupa. Among its treasured collection, the museum houses stone carvings, Buddha images, metal coins, clay vessels, bronze lamps, and the famous \"Elephant Lamp\" (Eth Pahana)—an ingenious automatic oil lamp designed entirely on hydrostatic principles.', 'Dedigama village, Warakapola Divisional Secretariat, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.21010724, 80.26435568, 'Museum: 8:00 AM - 5:00 PM Daily', 'Free entry', 'Parking available, Museum exhibits, Information boards, Walking paths, Shade trees', 'For a comfortable climate, it is best to plan your visit during the morning. Conveniently located just 4 km from the Nelumdeniya junction on the Colombo-Kandy road, this destination is easy to combine with other nearby attractions in the Warakapola area. The site is highly suitable for history and archaeology enthusiasts, and taking a guide is recommended for deeper historical insights. Always remember to carry water and sun protection for your trip.', 'Please follow all site rules and be deeply respectful of the location\'s religious significance. To ensure preservation and personal safety, do not climb on any ancient structures and carefully watch your step on uneven surfaces. When exploring the museum, avoid touching the artifacts and note that photography is strictly prohibited inside without prior permission.', '/assets/kotawehera.webp'),
(5, 2, NULL, 'Ambepussa Railway Station & Heritage Rest House', 'Ambepussa holds a unique place in Sri Lanka\'s transport history, primarily as its railway station served as the terminus for the country\'s first rail passenger service—a ten-carriage train that departed Colombo Fort on 22 October 1865, though the very first train had actually arrived at the station earlier on 27 December 1864. Built by the British Colonial Government, this railway line initially ran only up to Ambepussa before being successfully extended to Kandy in 1867. Adding to the area\'s rich heritage is the Heritage Ambepussa Hotel (formerly the Ambepussa Resthouse), which proudly stands as the country\'s oldest purpose-built hostelry. Originally built in 1822 as the residence for the Public Works Department Executive Engineer during the construction of the Colombo-Kandy road, it was later converted into a resthouse in 1828. This classic single-storey, Dutch-style bungalow features seven rooms adorned with white circular columns and has hosted many distinguished visitors throughout history, including British royalty. Today, it beautifully offers a blend of colonial charm and modern comfort, complete with an on-site restaurant serving authentic Sri Lankan and Western cuisine.', 'Ambepussa Junction, Gampaha District, Sabaragamuwa Province, Sri Lanka', 7.24180000, 80.21120000, 'Rest House: Open 24 hours (Hotel services) / Railway Station: As per train schedule', 'Free entry to station and rest house premises', 'Rest House: Restaurant (Western, Eastern & Sri Lankan cuisine), Bar, Garden, Free parking, Children\'s playground. Railway Station: Basic passenger facilities, Platform access', 'For better photographs, it is highly recommended to visit during the daytime. Conveniently located just 1 km from the Warakapola Bus Station at the junction of the A1 (Colombo-Kandy) and A6 (Ambepussa-Trincomalee) highways, this destination is very easy to combine with other nearby Warakapola attractions. Furthermore, the rest house serves as a popular stop-over for tourists traveling to Kandy, Dambulla, Sigiriya, and Polonnaruwa, making it the perfect opportunity to try the authentic Sri Lankan breakfast buffet offered at the venue.', 'When exploring the area, please be cautious when crossing the railway tracks and strictly follow all railway station rules by ensuring you do not enter any restricted areas. Visitors are kindly requested to respect the heritage building, and please note that there is absolutely no photography allowed inside the hotel rooms without prior permission.', '/assets/station.webp'),
(6, 5, NULL, 'Theli Ella Waterfall', 'Lying off the Nelundeniya-Galapitamada road close to Dedigama town, Theli Ella is a beautiful waterfall and ecological zone created by the Gurugoda Oya as it flows across a large rock plateau. Though not a significantly tall waterfall, the area beautifully comprises three small cascading waterfalls—historically named Theli Falls, Gabada Falls, and Kanawandum Falls—alongside numerous other small waterfalls and rocky outcrops filled with still water in the vicinity. The site is deeply associated with various folk stories, most notably one linked to King Walagamba. According to legend, the king and queen used to bathe in these waters, and one day, after a gold plate (or a necklace) fell from the queen\'s hands into the pool, the location was famously named \"Thali Ella,\" which eventually evolved into Theli Ella.', 'Theliella, Thunthota Dedigama, Warakapola, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.21670000, 80.26450000, 'Open 24 hours Daily', 'Free entry', 'Parking (limited), Natural swimming pools, Rocky outcrops, Scenic views', 'Since access to the waterfall is difficult as it lies far from any road, taking a bike ride is much better than using a car. For the best experience, visit during the rainy season for better water flow, and it is best visited with a local guide who knows the area well. The destination is highly suitable for nature lovers and photography enthusiasts. Be sure to wear comfortable shoes with good grip and remember to carry water and snacks for your journey.', 'Because the area is remote, please inform someone about your visit and always carry a mobile phone with emergency contacts, especially since there are no lifeguards or safety facilities available. Watch your step on the rocky outcrops and be very careful when walking, as the rocks can be highly slippery. Furthermore, do not attempt to visit during heavy rain, as the water levels can rise quickly.', '/assets/theli.webp'),
(7, 5, NULL, 'Galgediyana Waterfall (Galgediyana Ella)', 'Situated in the Gurugoda Oya (river) within the Kegalle District, Galgediyana Ella is a unique waterfall located between the Parambe and Hunugal Estates, about 3-4 km from Morontota town. The Sinhala name \"Galgedi\" literally translates to \"heap of rocks,\" which accurately describes the rocky formations that shape its cascading waters. While its height is a modest 5 meters, the waterfall is remarkably wide at approximately 50 meters, creating a spectacular curtain-like cascade that spans across a large rock plateau. Though modest in height, this impressive width gives the cascade a unique character and officially makes it one of the widest waterfalls in Sri Lanka. Enhancing this natural beauty, the surrounding area is remarkably rich in biodiversity, with dense foliage and the soothing sounds of nature perfectly adding to the waterfall\'s charm.', 'Gurugoda Oya, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.15760000, 80.18390000, 'Open 24 hours Daily', 'Free entry', 'Parking (limited), Natural pool at base, Scenic views, Photography spots', 'For the most majestic experience, this destination is best visited during the rainy season. Conveniently, the falls can be viewed directly from the highway, which is about 15m below. The wide cascade creates a stunning backdrop that is absolutely ideal for photography, while the location itself is highly suitable for picnics and short nature walks. To enhance your visit, don\'t hesitate to ask locals for directions and insights about the area. Finally, please remember to keep the area clean and always respect the natural environment.', 'Please be careful when walking and strictly watch your step on the rocky surfaces, as the rocks can be highly slippery. Since there are no lifeguards or safety facilities available at the location, it is crucial that you do not visit during heavy rain, as water levels can rise very quickly. Additionally, always remember to carry water and sun protection to ensure a safe and comfortable trip.', '/assets/galgediyana.webp'),
(8, 1, NULL, 'Arandara Dewalaya', 'Situated along the Galigamuwa-Ruwanwella Road in the Kegalle District, Arandara Dewalaya is a revered religious and cultural site well-known among local travellers. Positioned on an important route connecting the Kegalle and Avissawella areas, this sacred complex is deeply associated with a Buddhist temple and traditional devalaya worship. According to local sources, the religious grounds feature a specific shrine dedicated to Dedimunda, alongside the Aluthnuwara Dewalaya and Vishnu Dewalaya. Enhancing its vibrant cultural significance, the site proudly hosts an annual festival (perahera) in March.', '52/2, Galigamuwa-Ruwanwella Road, Atala, Arandara, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.18302010, 80.29782670, 'Please contact the attraction to confirm specific opening hours', 'Free entry', 'Parking available, Restrooms, Shrine rooms, Festival grounds', 'For a truly vibrant cultural experience, it is highly recommended to visit during the annual March perahera festival. Easily accessible and conveniently located on the Kegalle-Avissawella main road—exactly where the Boyagoda road starts—this site is best visited during the daytime. It can also be perfectly combined with a trip to the nearby Dedigama Kota Vehera and other Warakapola attractions. When visiting, please ensure you dress modestly out of respect for the sacred environment.', 'Visitors are kindly requested to strictly follow all temple rules and customs, which includes removing your shoes before entering any shrine areas. Please be deeply respectful of worshippers and ongoing rituals, and note that there is absolutely no photography permitted inside the inner sanctums. Additionally, always remember to drive carefully on the main road when approaching or leaving the destination.', '/assets/arandara.webp'),
(9, 5, NULL, 'Algama Waterfall (Algama Ella)', 'Located adjacent to Algama town in the Kegalle District of the Sabaragamuwa Province, Algama Ella is a beautiful, modest waterfall standing at approximately 6 meters (20 feet) high. Nestled within the peaceful countryside of Algama village, the cascade is surrounded by lush vegetation and features a natural pool at its base that is perfect for swimming. Thanks to a convenient rocky ledge nearby, the falls can be viewed from very close. Offering a serene and tranquil atmosphere, it is an ideal location for those seeking peace and quiet in the wilderness, allowing visitors to enjoy picnicking by the falls and walking along the picturesque trails. Additionally, this beautiful waterfall is part of the exact same river system that also feeds the nearby Dunumala Ella.', 'Uduwaka - Algama Road, Algama, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.15320000, 80.17460000, 'Open 24 hours Daily', 'Free entry', 'Parking available (car park with short footpath), Natural swimming pool, Rocky ledges, Picnic areas, Scenic views', 'To reach the location from Colombo, take the route through Kadawatha, Nittambuwa, Pasyala, Meewitiya Road, Haggalla Road, and Uduwaka Road, which is a journey of approximately 54km and takes about 1 hour and 50 minutes. Alternatively, from Nittambuwa, you can take the Algama Bus to Algama Town and then travel about 500m along the Algama-Uduwaka road. Please note that the falls can be approached by vehicle only up to a certain extent, after which a short footpath leads directly to the stream. For this trek over the rocks, be sure to wear comfortable shoes with good grip. The destination is highly suitable for swimming, picnics, and nature photography, and it is best to visit during the rainy season to experience better water flow.', 'When exploring the area, be very careful when walking and climbing, as the rocks can be highly slippery, and always watch your step on the rocky surfaces. Since there are no lifeguards or safety facilities available, it is crucial that you do not visit during heavy rain, as water levels can rise very quickly. Remember to carry water and sun protection for your trip, and please ensure you take all your trash back with you to protect the natural environment.', '/assets/algama.webp'),
(10, 1, NULL, 'Attanagalla Raja Maha Viharaya', 'Attanagalla Raja Maha Viharaya is one of the most famous ancient royal temples in Sri Lanka, boasting a rich history that traces back to the Anuradhapura era when the monastery was built by Kings Gotabhaya and Upathissa. It holds the unique distinction of being the only ancient temple historically recognized as the place where a Bodhisattva (a future Buddha) fulfilled his Dana Paramita, which is one of the ten perfections required to become a Buddha. According to legend, it was here that King Sirisangabo sacrificed his life by offering his head to complete his \"paramitha.\" Although the temple was subsequently destroyed by the Portuguese and abandoned to the wilderness, it endures today and proudly features the only intact vatadage in the country. Within this sacred complex, visitors will find a stupa enclosed inside the vatadage, an additional stupa, a revered sacred Bodhiya, and several statues that notably resemble the Mahayana style. Preserving its immense historical and cultural significance, the temple was officially declared an Archaeological Protected Monument in the Gampaha District on 1 November 1996.', 'Attanagalla Temple Rd, Gampaha 11000, Sri Lanka / Nittambuwa - Ruwanwella Rd, Sri Lanka', 7.11610000, 80.12990000, '6:00 AM - 8:00 PM Daily', 'Free entry (Donations welcome)', 'Parking available (be careful of falling coconuts), Restrooms/Washrooms, Flower sellers (unique \"siyak pethi\" lotus flowers with hundred petals), Ancient iron chain along the steps, Kandy half-moon step', 'For the best spiritual atmosphere, it is highly recommended to visit during the morning or evening. The temple is beautifully surrounded by lush greenery, offering a peaceful and meditative environment. During your visit, be sure to observe the unique \"watadage\"—a rare wooden building that serves as a dome of protection for the dagoba—and take the opportunity to climb the fifty stone steps equipped with an ancient iron chain. For your offerings, you can easily buy fresh flowers from within the premises, though please note that prices may be slightly higher than outside.', 'When visiting, please respect all religious customs and traditions by strictly following temple rules and any instructions given by the monks. Ensure you remove your shoes before entering the sacred areas, and remember that there is absolutely no photography permitted inside the inner sanctums without prior permission. For your personal safety, carefully watch your step on the stone steps and be cautious of falling coconuts when parking your vehicle near the coconut trees.', '/assets/attanagalla.webp'),
(11, 4, NULL, 'Salgala Forest Monastery', 'Located on a mountain top surrounded by 600 acres of dense government forest reserve locally known as \"Ketagilla Mookalana,\" Salgala Forest Monastery proudly stands as one of the oldest forest monasteries in Sri Lanka. Its rich historical background traces back to the reign of King Walagamba (103 AD), who famously used this sanctuary as a hideout from Chola invaders. Welcoming visitors is a striking stone-carved entrance—donated by the first Prime Minister, D.S. Senanayaka—that closely resembles those found at the Ritigala and Arankele hermitages. Within the sacred grounds, the monastery features 18 natural rock caves perfectly suitable for meditation, all of which uniquely take their names from Sanskrit. The site is also home to remarkable historical features, including a stone inscription in Asokan letters dating back to 104 BC, the \"Etha Bendi Lena\" where the king\'s elephants were once chained, and \"Belumgala,\" a spectacular panoramic lookout point. From this high vantage, the monastery offers breathtaking views of Sri Pada and the vast surrounding \"Green Ocean.\"', 'Welhella Grama Niladari Division, Galapitamada, Warakapola Divisional Secretariat, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.12280000, 80.24960000, 'Gate open: 6:00 AM - 5:00 PM Daily', 'Free entry (Donations welcome)', '18 meditation caves (kutis), Paved meditation walks, Stone pathways, Ancient library cave (Poth Gula), Dana shalawa (alms hall), Gandhakuti vihara, Ananda Bodhi sapling from India, Natural forest trails, Stream pools, Belumgala viewpoint rock', 'Visit during the dry season (December–April) at sunrise for cool temperatures and active wildlife. From Warakapola, travel 2.3km to the Galapitamada junction, then 1.5km to the entrance. From Kandy, take the Colombo-Kandy road (via Peradeniya, Kadugannawa, Mawanella, and Kegalle) to Nelundeniya, then use the Dedigama-Galapitamada road. Dress modestly in white or light-colored clothing that covers your shoulders and knees. Wear grippy shoes for slippery paths, carry insect repellent for leeches, and maintain complete silence to respect the meditating monks.', 'Exercise caution on slippery rocks and steps, and wear footwear suitable for leech-prone areas. Avoid midday visits due to high humidity, always carry water, and inform someone of your trip since the location is remote. To ensure perfect silence, the grounds close in the evening, and visitors must never disturb the monks or meditation areas. Finally, filming is strictly prohibited; all film and teledrama crews are completely banned to protect the local wildlife.', '/assets/salgala.webp'),
(12, 3, NULL, 'Dorawaka Prehistoric Cave (Dorawaka Athubendi Lena)', 'Dating back to the Mesolithic Era (approx. 6300 BC) and initially discovered in the 1940s, the Dorawaka Prehistoric Cave is one of Sri Lanka\'s most significant archaeological sites. The striking structure is formed by two large, triangular-shaped boulders leaning together, standing over 55 feet (16.7 meters) high with an 82 by 14-foot floor space. Believed to be of Veddah origin, primitive rock paintings are found approximately 6.5 feet above ground, depicting hunters, an elephant and its calf, the sun, the moon, a conch, fish, and other unidentifiable objects. Extensive excavations have uncovered fireplaces, Stone Age pottery, charcoal, stone tools, microliths, alongside millet and cereal seeds. Furthermore, the cave houses several Brahmi inscriptions—representing some of the country\'s earliest evidence of lettering—and was officially declared an Archaeological Protected Monument on November 22, 2002.', 'Dorawaka village, Warakapola Divisional Secretariat Division, Kegalle District, Sabaragamuwa Province, Sri Lanka', 7.19380000, 80.21520000, 'Open 24 hours Daily', 'Free entry', 'Parking (limited - roadside), Cave entrance, Archaeological markings numbered by Department of Archaeology, Local guide available nearby (about 600m from cave), Signboard by Department of Archaeology', 'Travel from Colombo to Warakapola (67km, 1h 44m), then 5km via the B457 road. The cave is inside a rubber plantation requiring a 1km walk, so motorcycles are preferred over cars. Visit during the daytime in comfortable walking shoes, and bring a flashlight to see the carvings. You can combine this trip with Dedigama Kota Vehera, and view a site documentary at the local official\'s house 600m away.', 'A flashlight is essential for the dark interior, where you must watch your step on uneven terrain and be aware of bats. Since this remote site lacks nearby shops, carry water and daily essentials, and inform someone about your visit. Strictly respect this protected monument by never touching the ancient carvings, and always follow instructions from the local archaeological guide.', '/assets/dorawaka.webp');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(3, 'Archaeological'),
(8, 'Eco-Tourism'),
(7, 'Heritage'),
(2, 'Historical'),
(4, 'Nature'),
(1, 'Religious'),
(5, 'Waterfalls');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary`
--

CREATE TABLE `itinerary` (
  `itinerary_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_attraction`
--

CREATE TABLE `itinerary_attraction` (
  `itinerary_id` int(11) NOT NULL,
  `attraction_id` int(11) NOT NULL,
  `day_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `attraction`
--
ALTER TABLE `attraction`
  ADD PRIMARY KEY (`attraction_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `itinerary`
--
ALTER TABLE `itinerary`
  ADD PRIMARY KEY (`itinerary_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `itinerary_attraction`
--
ALTER TABLE `itinerary_attraction`
  ADD PRIMARY KEY (`itinerary_id`,`attraction_id`),
  ADD KEY `attraction_id` (`attraction_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `attraction`
--
ALTER TABLE `attraction`
  MODIFY `attraction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `itinerary`
--
ALTER TABLE `itinerary`
  MODIFY `itinerary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attraction`
--
ALTER TABLE `attraction`
  ADD CONSTRAINT `attraction_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  ADD CONSTRAINT `attraction_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `administrator` (`admin_id`);

--
-- Constraints for table `itinerary`
--
ALTER TABLE `itinerary`
  ADD CONSTRAINT `itinerary_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `administrator` (`admin_id`);

--
-- Constraints for table `itinerary_attraction`
--
ALTER TABLE `itinerary_attraction`
  ADD CONSTRAINT `itinerary_attraction_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itinerary` (`itinerary_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itinerary_attraction_ibfk_2` FOREIGN KEY (`attraction_id`) REFERENCES `attraction` (`attraction_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
