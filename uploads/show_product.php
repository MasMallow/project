<?php
include 'condb.php'
    ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="boostrap/css/bootstrap.rtl.min.css">
    <title>shop</title>
</head>

<body>
    <div class="container text-center">

        <div class="row">
            <?php
            $sql = "SELECT * FROM image ORDER BY id";
            $result = mysqli_query($db, $sql);
            while ($row = mysqli_fetch_array($result)) {
                $imageURL = '/test' . $row['file_name'];
                    ?>
                    <div class="col-sm-3">
                        <img src="img/<?= $row['file_name'] ?>" width="200px" height="250px"><br>
                        ID :
                        <?= $row['id'] ?><br>
                    </div>
                    <?php
                }
            mysqli_close($db);
            ?>
        </div>
    </div>
    <script src="boostrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>