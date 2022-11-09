-- phpMyAdmin SQL Dump
-- version 4.6.6deb4+deb9u2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 09, 2022 at 06:12 PM
-- Server version: 10.1.48-MariaDB-0+deb9u2
-- PHP Version: 5.6.40-57+0~20211119.60+debian9~1.gbp8a9bd1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `simpledam_skel`
--

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_assets`
--

CREATE TABLE `simpledam_assets` (
  `assetid` int(25) NOT NULL,
  `publicassetid` varchar(255) DEFAULT NULL,
  `hashid` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4,
  `userid` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `datemodified` datetime NOT NULL,
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table to storte original image references';

--
-- Dumping data for table `simpledam_assets`
--

INSERT INTO `simpledam_assets` (`assetid`, `publicassetid`, `hashid`, `metadata`, `userid`, `datecreated`, `datemodified`, `isdeleted`) VALUES
(1, '356a192b7913b04c54574d18c28d46e6395428ab', '20221011_7fe98f08651c1634e7559ad0d6ab27e3', '{\"filename\":\"IMG_8142.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image/jpeg\",\"filesize\":338648,\"fullwidth\":1728,\"fullheight\":1152,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"Sunflower\",\"uploader\":\"Example User\",\"views\":2,\"downloads\":0},\"exif\":{\"IFD0\":{\"Make\":\"Canon\",\"Model\":\"Canon EOS 350D DIGITAL\",\"Orientation\":1,\"XResolution\":\"72/1\",\"YResolution\":\"72/1\",\"ResolutionUnit\":2,\"Software\":\"Adobe Photoshop CS Windows\",\"DateTime\":\"2011:08:17 01:16:02\",\"YCbCrPositioning\":2,\"Exif_IFD_Pointer\":228},\"EXIF\":{\"ExposureTime\":\"1/1250\",\"FNumber\":\"14/10\",\"ExposureProgram\":1,\"ISOSpeedRatings\":200,\"ExifVersion\":\"0221\",\"DateTimeOriginal\":\"2011:07:19 18:45:49\",\"DateTimeDigitized\":\"2011:07:19 18:45:49\",\"ComponentsConfiguration\":\"\\u0001\\u0002\\u0003\",\"ShutterSpeedValue\":\"674216/65536\",\"ApertureValue\":\"63626/65536\",\"ExposureBiasValue\":\"0/2\",\"MeteringMode\":5,\"Flash\":16,\"FocalLength\":\"50/1\",\"UserComment\":\"\",\"FlashPixVersion\":\"0100\",\"ColorSpace\":1,\"ExifImageWidth\":1728,\"ExifImageLength\":1152,\"InteroperabilityOffset\":928,\"FocalPlaneXResolution\":\"3456000/874\",\"FocalPlaneYResolution\":\"2304000/582\",\"FocalPlaneResolutionUnit\":2,\"CustomRendered\":0,\"ExposureMode\":1,\"WhiteBalance\":0,\"SceneCaptureType\":0}}}}', 1, '2022-08-23 16:38:21', '2022-10-11 11:38:09', 0),
(2, 'da4b9237bacccdf19c0760cab7aec4a8359010b0', '20221011_9428793d5eaf64a0a9410fbd3b9f7a5d', '{\"filename\":\"oli-woodman-ol1dX9NkjEI-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":473112,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"an old retro orange Volkswagen Beetle car\",\"uploader\":\"Example User\",\"views\":10,\"downloads\":1}}}', 1, '2022-10-06 14:12:44', '2022-10-11 11:38:39', 0),
(3, '77de68daecd823babbb58edb1c8e14d7106e83bb', '20221011_e5bd6a3934706daadccc1151a292c8b0', '{\"filename\":\"sample1.m4a\",\"extension\":\"m4a\",\"mimetype\":\"audio\\/x-m4a\",\"filesize\":2034429,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example M4A Audio File\",\"uploader\":\"Example User\",\"views\":98,\"downloads\":5}}}', 1, '2022-10-07 04:34:57', '2022-10-11 11:54:15', 0),
(4, '1b6453892473a467d07372d45eb05abc2031647a', '20221011_269be16d2093dba73d0d4cb083877b8c', '{\"filename\":\"Example-Word-Document.docx\",\"extension\":\"docx\",\"mimetype\":\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"filesize\":15213,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example Word Document\",\"uploader\":\"Example User\",\"views\":14,\"downloads\":2}}}', 1, '2022-10-07 04:35:25', '2022-10-11 11:37:25', 0),
(5, 'ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4', '20221011_a50be80b99737ae6b6d4ed69d83fcdf1', '{\"filename\":\"example-video.mp4\",\"extension\":\"mp4\",\"mimetype\":\"video\\/mp4\",\"filesize\":2605675,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":350,\"previewheight\":196,\"extensions\":{\"simpledam\":{\"description\":\"Example MP4 Video File\",\"uploader\":\"Example User\",\"views\":24,\"downloads\":3}}}', 1, '2022-10-07 04:35:41', '2022-10-11 11:35:28', 0),
(6, 'c1dfd96eea8cc2b62785275bca38ac261256e278', '20221011_305ca934fd31d38c74e2573eaa28dd28', '{\"example\":{\"string\":\"test\",\"integer\":123,\"boolean\":true,\"float\":1.234,\"array\":[{\"element\":\"one\"},{\"element\":\"two\"},{\"element\":\"three\"}],\"object\":{\"property\":\"value\",\"property2\":\"value\"}},\"filename\":\"richard-burlton-COhv_NUvIRw-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":336803,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"A raccoon peering from behind a tree\",\"uploader\":\"Example User\",\"views\":11,\"downloads\":0}}}', 1, '2022-10-07 04:36:46', '2022-10-11 11:34:35', 0),
(7, '902ba3cda1883801594b6e1b452790cc53948fda', '20221011_f6ab566792f5a6e99b337c4d3db8ea4d', '{\"filename\":\"tyler-nix-6mze64HRU2Q-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":1140970,\"fullwidth\":1920,\"fullheight\":2880,\"previewwidth\":233,\"previewheight\":350,\"extensions\":{\"simpledam\":{\"description\":\"retro orange VW camper van on the beach\",\"uploader\":\"Example User\",\"views\":4,\"downloads\":1}}}', 1, '2022-10-07 05:28:13', '2022-10-11 11:34:16', 0),
(8, 'fe5dbbcea5ce7e2988b8c69bcfdfde8904aabc1f', '20221011_9cb0bf39957c9f4f6e2aad3852df2f5c', '{\"filename\":\"stephanie-leblanc-JLMEZxBcXCU-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":179571,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"A green tree frog\",\"uploader\":\"Example User\",\"views\":1,\"downloads\":0}}}', 1, '2022-10-07 07:10:44', '2022-10-11 11:33:57', 0),
(9, '0ade7c2cf97f75d009975f4d720d1fa6c19f4897', '20221011_51df7132facdad3f74115dfe5a708303', '{\"filename\":\"IMG_1150.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image/jpeg\",\"filesize\":1797157,\"fullwidth\":2592,\"fullheight\":1944,\"previewwidth\":350,\"previewheight\":262,\"extensions\":{\"simpledam\":{\"description\":\"London Bridge street sign\",\"uploader\":\"Example User\",\"views\":2,\"downloads\":1},\"exif\":{\"IFD0\":{\"Make\":\"Canon\",\"Model\":\"Canon DIGITAL IXUS 500\",\"Orientation\":1,\"XResolution\":\"180/1\",\"YResolution\":\"180/1\",\"ResolutionUnit\":2,\"DateTime\":\"2007:04:06 16:59:01\",\"YCbCrPositioning\":1,\"Exif_IFD_Pointer\":196},\"EXIF\":{\"ExposureTime\":\"1/500\",\"FNumber\":\"28/10\",\"ExifVersion\":\"0220\",\"DateTimeOriginal\":\"2007:04:06 16:59:01\",\"DateTimeDigitized\":\"2007:04:06 16:59:01\",\"ComponentsConfiguration\":\"\\u0001\\u0002\\u0003\",\"CompressedBitsPerPixel\":\"3/1\",\"ShutterSpeedValue\":\"287/32\",\"ApertureValue\":\"95/32\",\"ExposureBiasValue\":\"0/3\",\"MaxApertureValue\":\"95/32\",\"MeteringMode\":5,\"Flash\":24,\"FocalLength\":\"237/32\",\"MakerNote\":\"\\u0011\",\"UserComment\":\"\",\"FlashPixVersion\":\"0100\",\"ColorSpace\":1,\"ExifImageWidth\":2592,\"ExifImageLength\":1944,\"InteroperabilityOffset\":1860,\"FocalPlaneXResolution\":\"2592000/283\",\"FocalPlaneYResolution\":\"1944000/212\",\"FocalPlaneResolutionUnit\":2,\"SensingMethod\":2,\"FileSource\":\"\\u0003\",\"CustomRendered\":0,\"ExposureMode\":0,\"WhiteBalance\":0,\"DigitalZoomRatio\":\"2592/2592\",\"SceneCaptureType\":0}}}}', 1, '2022-10-07 07:11:28', '2022-10-11 11:33:33', 0),
(10, 'b1d5781111d84f7b3fe45a0852e59758cd7a87e5', '20221011_c5980f941e88487ab2232059bd601497', '{\"filename\":\"oli-woodman-yM2Uo_1xZ3I-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":381335,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"An orange retro Porsche car\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-07 07:28:49', '2022-10-11 11:33:09', 0),
(11, '17ba0791499db908433b80f37c5fbc89b870084b', '20221011_50cda6ea4f1e46de0447ed68857f5a2b', '{\"filename\":\"tim-mossholder-KsdgjODuJQE-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":323380,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"dog looking out of an orange car window\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-07 07:29:21', '2022-10-11 11:32:50', 0),
(12, '7b52009b64fd0a2a49e6d8a939753077792b0554', '20221011_95b06671f75aa67a8ac5551de5ef422e', '{\"filename\":\"hakon-sataoen-yQ9mZzBdDAM-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":278503,\"fullwidth\":1920,\"fullheight\":1207,\"previewwidth\":350,\"previewheight\":220,\"extensions\":{\"simpledam\":{\"description\":\"orange sports car against a stormy sky\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-07 07:29:52', '2022-10-11 11:32:26', 0),
(13, 'bd307a3ec329e10a2cff8fb87480823da114f8f4', '20221011_bb4376330a5c7f854f82f662bdcc40ff', '{\"filename\":\"boris-smokrovic-lyvCvA8sKGc-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":268161,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"butterfly on an orange flower\",\"uploader\":\"Example User\",\"views\":10,\"downloads\":3}}}', 1, '2022-10-07 07:30:16', '2022-10-20 18:04:25', 0),
(14, 'fa35e192121eabf3dabf9f5ea6abdbcbc107ac3b', '20221011_61371665d1b59dcaff86340704ab9aba', '{\"filename\":\"sheraz-shaikh-Zx3kcU2Kw9E-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":365968,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"sliced oranges\",\"uploader\":\"Example User\",\"views\":2,\"downloads\":1}}}', 1, '2022-10-07 07:33:02', '2022-10-11 11:31:26', 0),
(15, 'f1abd670358e036c31296e66b3b66c382ac00812', '20221011_37fc01637ceb6150eb967c2dd4edb3f5', '{\"filename\":\"frank-okay-R1J6Z1cnJZc-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":629253,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"retro TV set on a suburban street at night\",\"uploader\":\"Example User\",\"views\":16,\"downloads\":1}}}', 1, '2022-10-07 07:38:25', '2022-10-11 11:31:05', 0),
(16, '1574bddb75c78a6fd2251d61e2993b5146201319', '20221011_8a8f3ec72c4a082e35fd337e6e2d9cbd', '{\"filename\":\"Sample-Spreadsheet.zip\",\"extension\":\"zip\",\"mimetype\":\"application\\/zip\",\"filesize\":351,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example ZIP File\",\"uploader\":\"Example User\",\"views\":2,\"downloads\":0}}}', 1, '2022-10-08 02:09:27', '2022-10-11 11:30:44', 0),
(17, '0716d9708d321ffb6a00818614779e779925365c', '20221011_042b54e844adc50352d1ccffedb530b9', '{\"filename\":\"addresses.csv\",\"extension\":\"csv\",\"mimetype\":\"text\\/csv\",\"filesize\":328,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example plain text CSV File\",\"uploader\":\"Example User\",\"views\":3,\"downloads\":2}}}', 1, '2022-10-08 02:11:53', '2022-10-11 11:30:03', 0),
(18, '9e6a55b6b4563e652a23be9d623ca5055c356940', '20221011_1c29d9e0c5495acf1e07e874da250dca', '{\"example\":\"Here\'s an example metadata node\",\"filename\":\"readme.txt\",\"extension\":\"txt\",\"mimetype\":\"text\\/plain\",\"filesize\":3001,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example plain text file\",\"uploader\":\"Example User\",\"views\":2,\"downloads\":2}}}', 1, '2022-10-08 02:15:41', '2022-10-11 11:29:41', 0),
(19, 'b3f0c7f6bb763af1be91d9e74eabfeb199dc1f1f', '20221011_358fe8050f827c30e3055f2ead3f6b4e', '{\"filename\":\"philipp-pilz-QZ2EQuPpQJs-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":443146,\"fullwidth\":1920,\"fullheight\":2880,\"previewwidth\":233,\"previewheight\":350,\"extensions\":{\"simpledam\":{\"description\":\"A snarling wolf\",\"uploader\":\"Example User\",\"views\":5,\"downloads\":2}}}', 1, '2022-10-08 02:18:11', '2022-10-11 11:29:14', 0),
(20, '91032ad7bbcb6cf72875e8e8207dcfba80173f7c', '20221011_c03769c3e30ea0d4527aa90eaa989492', '{\"filename\":\"nguyen-dang-hoang-nhu-SjqrtZOd9Uc-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":714647,\"fullwidth\":1920,\"fullheight\":2880,\"previewwidth\":233,\"previewheight\":350,\"exif\":[],\"extensions\":{\"simpledam\":{\"description\":\"retro orange telephone\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":1}}}', 1, '2022-10-08 03:37:24', '2022-10-11 11:28:53', 0),
(21, '472b07b9fcf2c2451e8781e944bf5f77cd8457c8', '20221011_c5f8a8db7153db40ddb74814a0264d69', '{\"filename\":\"bg.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":216386,\"fullwidth\":1920,\"fullheight\":1200,\"previewwidth\":350,\"previewheight\":218,\"exif\":[],\"extensions\":{\"simpledam\":{\"description\":\"Graphic of a Japanese-inspired landscape\",\"uploader\":\"Example User\",\"views\":6,\"downloads\":2}}}', 1, '2022-10-08 12:04:29', '2022-10-11 11:28:25', 0),
(22, '12c6fc06c99a462375eeb3f43dfd832b08ca9e17', '20221011_15d57c8a0f0f7a12b4cae41130351d68', '{\"filename\":\"epicurrence-p6mPhlNCfMw-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":586435,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"A retro Volkswagen camper van\",\"uploader\":\"Example User\",\"views\":3,\"downloads\":2}}}', 1, '2022-10-08 12:35:54', '2022-10-11 11:27:23', 0),
(23, 'd435a6cdd786300dff204ee7c2ef942d3e9034e2', '20221011_00cf48ef7f06bb5adef198f4bb9c2849', '{\"filename\":\"example-photoshop-file.psd\",\"extension\":\"psd\",\"mimetype\":\"image\\/vnd.adobe.photoshop\",\"filesize\":140211,\"fullwidth\":842,\"fullheight\":595,\"previewwidth\":350,\"previewheight\":247,\"extensions\":{\"simpledam\":{\"description\":\"Example Photoshop file\",\"uploader\":\"Example User\",\"views\":21,\"downloads\":3}}}', 1, '2022-10-08 17:23:26', '2022-10-11 11:26:59', 0),
(24, '4d134bc072212ace2df385dae143139da74ec0ef', '20221011_a9023e97f46ec26898a5e49b3e2e231a', '{\"filename\":\"example-pdf-file.pdf\",\"extension\":\"pdf\",\"mimetype\":\"application\\/pdf\",\"filesize\":716951,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":247,\"previewheight\":350,\"extensions\":{\"simpledam\":{\"description\":\"Example PDF File\",\"uploader\":\"Example User\",\"views\":13,\"downloads\":5}}}', 1, '2022-10-10 13:45:50', '2022-10-11 11:26:42', 0),
(25, 'f6e1126cedebf23e1463aee73f9df08783640400', NULL, '{\"filename\":null,\"extension\":null,\"mimetype\":null,\"filesize\":0,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example Metadata-Only Asset\",\"uploader\":\"Example User\",\"views\":3,\"downloads\":0}}}', 1, '2022-10-10 16:06:31', '2022-10-10 16:16:10', 0),
(26, '887309d048beef83ad3eabf2a79a64a389ab1c9f', '20221011_e5616feafe798773197f17f82a380bea', '{\"filename\":\"sample1.mp3\",\"extension\":\"mp3\",\"mimetype\":\"audio\\/mpeg\",\"filesize\":1954212,\"fullwidth\":null,\"fullheight\":null,\"previewwidth\":null,\"previewheight\":null,\"extensions\":{\"simpledam\":{\"description\":\"Example MP3 Audio File\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-11 11:54:45', '2022-10-11 11:55:17', 0),
(27, 'bc33ea4e26e5e1af1408321416956113a4658763', '20221011_f9882d198852186f839f15f5f0f10648', '{\"filename\":\"paul-steuber-rs11hu-bkTc-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":144355,\"fullwidth\":1920,\"fullheight\":1280,\"previewwidth\":350,\"previewheight\":233,\"extensions\":{\"simpledam\":{\"description\":\"Nike swoosh on front of a building\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-11 11:55:57', '2022-10-11 11:56:22', 0),
(28, '0a57cb53ba59c46fc4b692527a38a87c78d84028', '20221011_ee8d6b3e2438441b5caf26d70f1de9af', '{\"filename\":\"altinay-dinc-LluELtL5mK4-unsplash.jpg\",\"extension\":\"jpg\",\"mimetype\":\"image\\/jpeg\",\"filesize\":156303,\"fullwidth\":1920,\"fullheight\":2880,\"previewwidth\":233,\"previewheight\":350,\"extensions\":{\"simpledam\":{\"description\":\"Dramatic cloudy sky with moon\",\"uploader\":\"Example User\",\"views\":0,\"downloads\":0}}}', 1, '2022-10-11 11:58:45', '2022-10-24 15:20:01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_events`
--

CREATE TABLE `simpledam_events` (
  `eventid` int(25) NOT NULL,
  `eventtypeid` tinyint(2) NOT NULL,
  `userid` smallint(3) NOT NULL,
  `assetid` int(5) DEFAULT NULL,
  `eventip` varchar(50) DEFAULT NULL,
  `eventdetails` text NOT NULL,
  `apiurl` varchar(255) DEFAULT NULL,
  `apimethod` varchar(8) DEFAULT NULL,
  `eventdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='General event/audit log';

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_event_types`
--

CREATE TABLE `simpledam_event_types` (
  `eventtypeid` int(1) NOT NULL,
  `eventtypename` varchar(255) NOT NULL,
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `simpledam_event_types`
--

INSERT INTO `simpledam_event_types` (`eventtypeid`, `eventtypename`, `isdeleted`) VALUES
(1, 'log in', 0),
(2, 'log out', 0),
(3, 'get user', 0),
(4, 'list users', 0),
(5, 'add user', 0),
(6, 'update user', 0),
(7, 'get asset', 0),
(8, 'list assets', 0),
(9, 'download asset', 0),
(10, 'export asset', 0),
(11, 'import asset', 0),
(12, 'add asset', 0),
(13, 'add user role', 0),
(14, 'update user role', 0),
(15, 'upload asset', 0),
(16, 'update asset', 0),
(17, 'delete asset', 0),
(18, 'delete user', 0),
(19, 'delete user role', 0),
(20, 'get event type', 0),
(21, 'list event types', 0),
(22, 'add event type', 0),
(23, 'update event type', 0),
(24, 'delete event type', 0);

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_sessions`
--

CREATE TABLE `simpledam_sessions` (
  `sessionid` int(25) NOT NULL,
  `userid` int(25) NOT NULL,
  `sessiontoken` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sessiontimestamp` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_users`
--

CREATE TABLE `simpledam_users` (
  `userid` int(25) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userroleid` tinyint(2) NOT NULL,
  `lastlogindate` datetime DEFAULT NULL,
  `datecreated` datetime DEFAULT NULL,
  `datemodified` datetime DEFAULT NULL,
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `simpledam_users`
--

INSERT INTO `simpledam_users` (`userid`, `firstname`, `lastname`, `email`, `password`, `userroleid`, `lastlogindate`, `datecreated`, `datemodified`, `isdeleted`) VALUES
(1, 'Example', 'User', 'mail@example.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 1, NULL, '2022-08-15 15:18:05', '2022-08-15 15:18:05', 0),
(2, 'Admin', 'User', 'admin@example.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 2, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `simpledam_user_roles`
--

CREATE TABLE `simpledam_user_roles` (
  `userroleid` int(2) NOT NULL,
  `userrolename` varchar(25) NOT NULL,
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `simpledam_user_roles`
--

INSERT INTO `simpledam_user_roles` (`userroleid`, `userrolename`, `isdeleted`) VALUES
(1, 'Normal', 0),
(2, 'Administrator', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `simpledam_assets`
--
ALTER TABLE `simpledam_assets`
  ADD PRIMARY KEY (`assetid`);

--
-- Indexes for table `simpledam_events`
--
ALTER TABLE `simpledam_events`
  ADD PRIMARY KEY (`eventid`);

--
-- Indexes for table `simpledam_event_types`
--
ALTER TABLE `simpledam_event_types`
  ADD PRIMARY KEY (`eventtypeid`);

--
-- Indexes for table `simpledam_sessions`
--
ALTER TABLE `simpledam_sessions`
  ADD PRIMARY KEY (`sessionid`);

--
-- Indexes for table `simpledam_users`
--
ALTER TABLE `simpledam_users`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `simpledam_user_roles`
--
ALTER TABLE `simpledam_user_roles`
  ADD PRIMARY KEY (`userroleid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `simpledam_assets`
--
ALTER TABLE `simpledam_assets`
  MODIFY `assetid` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `simpledam_events`
--
ALTER TABLE `simpledam_events`
  MODIFY `eventid` int(25) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `simpledam_event_types`
--
ALTER TABLE `simpledam_event_types`
  MODIFY `eventtypeid` int(1) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `simpledam_sessions`
--
ALTER TABLE `simpledam_sessions`
  MODIFY `sessionid` int(25) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `simpledam_users`
--
ALTER TABLE `simpledam_users`
  MODIFY `userid` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `simpledam_user_roles`
--
ALTER TABLE `simpledam_user_roles`
  MODIFY `userroleid` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
