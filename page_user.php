<?php 

@include './config.php';

session_start();

if (!isset($_SESSION['name'])) {
    header('location:./login.php');
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Page-User</title>
   

</head>

<body>
    <div class="container">
        <div class="content">
            <h3>Hello , <span>User</span></h3>
            <h1>Welcome <span><?php echo $_SESSION['name'] ; ?></span></h1>
            <p>This Is An User Page</p>
            <a href="./login.php" class="btn">Login</a>
            <a href="./register.php" class="btn">Register</a>
            <a href="./logout.php" class="btn">Logout</a>
        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</body>

</html>