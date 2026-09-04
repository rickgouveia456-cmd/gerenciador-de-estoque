<?php
requer_login();
csrf_check();
session_destroy();
redirect('/login');
