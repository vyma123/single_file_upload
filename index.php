<?php 
require_once 'db.php';

function handleUpload($file, $target_dir) {
    global $overallUploadOk, $err_image; 

    if (isset($file) && $file["error"] == 0) {
        $target_file = $target_dir . basename($file["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($file["tmp_name"]);
        if ($check !== false) {
            echo '';
        } else {
            echo "File is not an image.<br>";
            $err_image = 'empty_field';
            $overallUploadOk = 0;
            return false;
        }

        if ($file["size"] > 500000) {
            echo "Sorry, file is too large.<br>";
            $err_image = 'empty_field';
            $overallUploadOk = 0;
            return false;
        }

        if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.<br>";
            $err_image = 'empty_field';
            $overallUploadOk = 0;
            return false;
        }

        // Nếu tất cả các kiểm tra đều OK, trả về đường dẫn tệp đã tải lên
        return $target_file;

    } else {
        $overallUploadOk = 0;
        return false;
    }
}

$overallUploadOk = 1;
$uploadedImages = [];   
$target_dir = "uploads/";
$uploadDir = "uploads";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_POST['uploadedImages'])) {
    $uploadedImages = json_decode($_POST['uploadedImages'], true);
}

if(isset($_POST['add'])){
    $name = $_POST['name'];
    $image = $_FILES['imagefile'];
    $targetFilePath = handleUpload($image, $target_dir); // Lưu kết quả từ handleUpload

    if ($overallUploadOk == 1 && !empty($image['name'])) {
        $imageTmpName = $image['tmp_name'];
        $targetFilePath = $uploadDir . '/' . basename($image['name']);

        // Xóa các ảnh cũ
        foreach ($uploadedImages as $oldImage) {
            if (file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        // Mặc định làm mới mảng uploadedImages
        $uploadedImages = []; 

        // Di chuyển tệp hình ảnh tải lên
        if (move_uploaded_file($imageTmpName, $targetFilePath)) {
            $uploadedImages[] = $targetFilePath; 
        } else {
            echo "<p style='color: red;'>Failed to upload image: {$image['name']}</p>";
        }
    }

    // Lưu vào cơ sở dữ liệu chỉ nếu tên không rỗng
    if (!empty($name) && !empty($targetFilePath)) {
        try {
            $imageFile1 = htmlspecialchars(basename($targetFilePath));

            $stmt = $pdo->prepare("INSERT INTO product (featured_image) 
                                        VALUES (:featured_image)");
            $stmt->bindParam(':featured_image', $imageFile1); 

            if ($stmt->execute()) {
                echo "<p style='color: green;'>Product saved to database successfully!</p>";
            } else {
                echo "<p style='color: red;'>Failed to save product to database.</p>";
            }
            $execute_success = 'successfully';
        } catch (Exception $e) { // Sửa lỗi cú pháp
            echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
        }
    }

    // Nếu không có tên nhưng có ảnh đã tải lên
    if (!empty($uploadedImages) && !empty($name)) {
        foreach ($uploadedImages as $image) {
            $imageFile = htmlspecialchars(basename($image));

            $stmt = $pdo->prepare("INSERT INTO product (featured_image) 
                                    VALUES (:featured_image)");
            $stmt->bindParam(':featured_image', $imageFile); 
            if ($stmt->execute()) {
                echo "<p style='color: green;'>Product saved to database successfully!</p>";
            } else {
                echo "<p style='color: red;'>Failed to save product to database.</p>";
            }
            $execute_success = 'successfully';
        }
    }
    
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Enter product name">
        <?php 
          foreach ($uploadedImages as $image) {
            echo "<img src='" . htmlspecialchars($image) . "' height='50' alt='Uploaded Image'>";
          }
        ?>
        <input type="file" name="imagefile">
        <input type="submit" name="add">
        <input type="hidden" name="uploadedImages" value='<?php echo htmlspecialchars(json_encode($uploadedImages)); ?>'> <!-- Lưu trữ ảnh đã tải lên -->
    </form>
</body>
</html>
