<?php

$conn = mysqli_connect("localhost","root","","gogograbs");

if (!$conn){
    die("Connection failed");
}

    $username = $_POST['username'];
    $password = $_POST['password'];

    $pass_hashed = md5($pass);

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn,$sql);

   if (mysqli_num_rows($result)>0){
    header("Location:odoo.html");
    exit();
   }else{
    echo"Login Failed";
   }
      
mysqli_close($conn);
?>