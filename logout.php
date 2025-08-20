<?php

include 'config.php';

include 'functions.php';

session_destroy();

redirect('login.php');

?>