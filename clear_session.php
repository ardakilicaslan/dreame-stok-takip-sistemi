<?php

session_start();

if (isset($_POST['clear_confirm'])) {

    unset($_SESSION['confirm_message']);

    unset($_SESSION['confirm_barcodes']);

    echo 'success';

} else {

    echo 'error';

}

?>