<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sesi Berakhir</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin : 20;
    padding : 20;
    box-sizing : border-box;
    font-family : 'Plus Jakarta Sans',sans-serif;
}


/* Card */

.card{

    position : absolute;

    top : 50%;
    left : 50%;

    transform : translate(-50%,-50%);

    width : 520px;

    background : white;

    border-radius : 24px;

    overflow : hidden;

    box-shadow : 0 25px 60px rgba(0,0,0,.35);

}

.card-top{

    height : 6px;

    background : #0C3A6B;

}

.card-body{

    padding : 34px;

}

a{

    display : inline-block;

    margin-top : 28px;

    padding : 14px 34px;

    background : #f4f4f5;

    color : #0C3A6B;

    text-decoration : none;

    border-radius:14px;

    font-weight : 700;

    transition : .2s;

}

a:hover{

    background: #0C3A6B;

    color: #f4f4f5;

}

</style>

</head>
<body>


<div class="card">

<div class="card-top"></div>

<div class="card-body">

<h1>
Sesi Anda Telah Berakhir
</h1>

<p>
Silakan Login Kembali Untuk Melanjutkan
</p>

<a href="login.php">
Login Kembali
</a>

</div>

</div>

</body>
</html>