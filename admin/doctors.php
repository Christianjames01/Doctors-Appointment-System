<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">  
    <link rel="stylesheet" href="../css/main.css">  
    <link rel="stylesheet" href="../css/admin.css">
        
    <title>Doctors</title>
    <style>
        .popup{
            animation: transitionIn-Y-bottom 0.5s;
        }
        .sub-table{
            animation: transitionIn-Y-bottom 0.5s;
        }
    </style>
</head>
<body>
    <?php

    session_start();

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])=="" or $_SESSION['usertype']!='a'){
            header("location: ../login.php");
        }

    }else{
        header("location: ../login.php");
    }
    
    //import database
    include("../connection.php");

    
    ?>
    <div class="container">
        <div class="menu">
            <table class="menu-container" border="0">
                <tr>
                    <td style="padding:10px" colspan="2">
                        <table border="0" class="profile-container">
                            <tr>
                                <td width="30%" style="padding-left:20px" >
                                    <img src="../img/user.png" alt="" width="100%" style="border-radius:50%">
                                </td>
                                <td style="padding:0px;margin:0px;">
                                    <p class="profile-title">Administrator</p>
                                    <p class="profile-subtitle"><?php echo substr($_SESSION['user'],0,25) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <a href="../logout.php" ><input type="button" value="Log out" class="logout-btn btn-primary-soft btn"></a>
                                </td>
                            </tr>
                    </table>
                    </td>
                
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-dashbord" >
                        <a href="index.php" class="non-style-link-menu"><div><p class="menu-text">Dashboard</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row menu-active menu-icon-doctor-active">
                    <td class="menu-btn menu-icon-doctor menu-active menu-icon-doctor-active">
                        <a href="doctors.php" class="non-style-link-menu non-style-link-menu-active"><div><p class="menu-text">Doctors</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-schedule">
                        <a href="schedule.php" class="non-style-link-menu"><div><p class="menu-text">Schedule</p></div></a>
                    </td>
                </tr>
                <tr class="menu-row">
                    <td class="menu-btn menu-icon-appoinment">
                        <a href="appointment.php" class="non-style-link-menu"><div><p class="menu-text">Appointment</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-patient">
                        <a href="patient.php" class="non-style-link-menu"><div><p class="menu-text">Patients</p></a></div>
                    </td>
                </tr>
                <tr class="menu-row" >
                    <td class="menu-btn menu-icon-settings">
                        <a href="settings.php" class="non-style-link-menu"><div><p class="menu-text">Settings</p></a></div>
                    </td>
                </tr>
                
            </table>
        </div>
        <div class="dash-body">
            <table border="0" width="100%" style=" border-spacing: 0;margin:0;padding:0;margin-top:25px; ">
                <tr >
                    <td width="13%">
                        <a href="doctors.php" ><button  class="login-btn btn-primary-soft btn btn-icon-back"  style="padding-left:40px;padding-top:12px;padding-bottom:12px;margin-left:20px;width:120px"><font class="tn-in-text">Back</font></button></a>
                    </td>
                    <td>
                        <form action="" method="post" class="header-search">

                            <input type="search" name="search" class="input-text header-search-input" placeholder="Search Doctor Name or Email" list="doctors">
                            
                            &nbsp;&nbsp;
                            <?php
                                // Fetch all doctor names and emails for datalist
                                $list11 = $database->query("select docname,docemail from doctor;");
                            ?>
                            
                            <datalist id="doctors">
                                <?php
                                    for ($y=0;$y<$list11->num_rows;$y++){
                                        $row00=$list11->fetch_assoc();
                                        $d=$row00["docname"];
                                        $c=$row00["docemail"];
                                        echo "<option value='$d'><br/>";
                                        echo "<option value='$c'><br/>";
                                    };
                                ?>

                            </datalist>
                            
                       
                            <input type="Submit" value="Search" class="login-btn btn-primary btn" style="padding-left: 25px;padding-right: 25px;padding-top: 10px;padding-bottom: 10px;">
                        
                        </form>
                    </td>
                    <td width="15%">
                        <p style="font-size: 14px;color: rgb(119, 119, 119);padding: 0;margin: 0;text-align: right;">
                            Today's Date
                        </p>
                        <p class="heading-sub12" style="padding: 0;margin: 0;">
                            <?php 
                        date_default_timezone_set('Asia/Kolkata');

                        $date = date('Y-m-d');
                        echo $date;
                        ?>
                        </p>
                    </td>
                    <td width="10%">
                        <button  class="btn-label"  style="display: flex;justify-content: center;align-items: center;"><img src="../img/calendar.svg" width="100%"></button>
                    </td>


                </tr>
             
                <tr >
                    <td colspan="4" style="padding-top:10px;">
                        <p class="heading-main12" style="margin-left: 45px;font-size:18px;color:rgb(49, 49, 49)">All Doctors (<?php echo $list11->num_rows; ?>)</p>
                    </td>
                    
                </tr>
                <?php
                    if($_POST){
                        $keyword=$_POST["search"];
                        
                        $sqlmain= "select * from doctor inner join specialties on doctor.specialties=specialties.id where docname='$keyword' or docemail='$keyword' or docemail like '$keyword%' or docname like '$keyword%'";
                    }else{
                        $sqlmain= "select * from doctor inner join specialties on doctor.specialties=specialties.id order by docid desc";
                    }



                ?>
                  
                <tr>
                   <td colspan="4">
                       <center>
                        <div class="abc scroll">
                        <table width="93%" class="sub-table scrolldown" border="0">
                        <thead>
                        <tr>
                                <th class="table-headin">
                                    Doctor Name
                                </th>
                                <th class="table-headin">
                                    Email
                                </th>
                                <th class="table-headin">
                                    Specialties
                                </th>
                                <th class="table-headin">
                                    Events
                                </th>
                        </tr>
                        </thead>
                        <tbody>
                        
                            <?php

                                $result= $database->query($sqlmain);

                                if($result->num_rows==0){
                                    echo '<tr>
                                    <td colspan="4">
                                    <br><br><br><br>
                                    <center>
                                    <img src="../img/notfound.svg" width="25%">
                                    
                                    <br>
                                    <p class="heading-main12" style="margin-left: 45px;font-size:20px;color:rgb(49, 49, 49)">We  couldnt find anything related to your keywords !</p>
                                    <a class="non-style-link" href="doctors.php"><button  class="login-btn btn-primary-soft btn"  style="display: flex;justify-content: center;align-items: center;margin-left:20px;"></button></a>
                                    </center>
                                    <br><br><br><br>
                                    </td>
                                    </tr>';
                                    
                                }
                                else{
                                for ( $x=0; $x<$result->num_rows;$x++){
                                    $row=$result->fetch_assoc();
                                    $docid=$row["docid"];
                                    $name=$row["docname"];
                                    $email=$row["docemail"];
                                    // Use the specialty name retrieved via JOIN
                                    $spe=$row["sname"]; 
                                    echo '<tr>
                                        <td>
                                            '.substr($name,0,30).'
                                        </td>
                                        <td>
                                            '.substr($email,0,20).'
                                        </td>
                                        <td>
                                            '.substr($spe,0,20).'
                                        </td>

                                        <td>
                                        <div style="display:flex;justify-content: center;">
                                        <a href="?action=edit&id='.$docid.'&error=0" class="non-style-link"><button  class="btn-primary-soft btn button-sm btn-edit"  style="padding-left: 20px;padding-right: 20px;margin-right: 10px;"><img src="../img/icons/edit.svg" width="100%"></button></a>
                                        <a href="?action=view&id='.$docid.'" class="non-style-link"><button  class="btn-primary-soft btn button-sm btn-view"  style="padding-left: 20px;padding-right: 20px;margin-right: 10px;"><img src="../img/icons/view.svg" width="100%"></button></a>
                                        <a href="?action=drop&id='.$docid.'&name='.$name.'" class="non-style-link"><button  class="btn-primary-soft btn button-sm btn-delete"  style="padding-left: 20px;padding-right: 20px;margin-right: 10px;"><img src="../img/icons/drop.svg" width="100%"></button></a>
                                        </div>
                                        </td>
                                    </tr>';
                                    
                                }
                            }
                                 
                            ?>
 
                        </tbody>

                    </table>
                    </div>
                    </center>
                   </td> 
                </tr>
                       
            </table>
        </div>
    </div>
    <?php 
    if($_GET){
        
        $id=$_GET["id"];
        $action=$_GET["action"];
        if($action=='drop'){
            $nameget=$_GET["name"];
            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <h2>Are you sure?</h2>
                            <a class="close" href="doctors.php">&times;</a>
                            <div class="content">
                                You want to Delete this Record?<br>('.substr($nameget,0,40).').
                                
                            </div>
                            <div style="display: flex;justify-content: center;">
                            <a href="delete-doctor.php?id='.$id.'" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;"<>&nbsp;Yes&nbsp;</button></a>
                            &nbsp;&nbsp;&nbsp;
                            <a href="doctors.php" class="non-style-link"><button  class="btn-primary btn"  style="display: flex;justify-content: center;align-items: center;margin:10px;padding:10px;">&nbsp;&nbsp;No&nbsp;&nbsp;</button></a>
    
                            </div>
                        </center>
                    </div>
                </div>
            ';
        }elseif($action=='view'){
            $sqlmain= "select * from doctor where docid='$id'";
            $result= $database->query($sqlmain);
            $row=$result->fetch_assoc();
            $name=$row["docname"];
            $email=$row["docemail"];
            $speid=$row["specialties"];
            
            $nic=$row['docnic'];
            $tele=$row['doctel'];

            // Fetch Specialty Name using the ID
            $spesql = "select sname from specialties where id='$speid'";
            $speres = $database->query($spesql);
            $spefetch = $speres->fetch_assoc();
            $spename = $spefetch["sname"];


            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                        <center>
                            <h2></h2>
                            <a class="close" href="doctors.php">&times;</a>
                            <div class="content">
                                eDoc Web App<br>
                                
                            </div>
                            <div style="display: flex;justify-content: center;">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            
                                <tr>
                                    <td>
                                        <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">View Details.</p><br><br>
                                    </td>
                                </tr>
                                
                                <tr>
                                    
                                    <td class="label-td" colspan="2">
                                        <label for="name" class="form-label">Name: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        '.$name.'<br><br>
                                    </td>
                                    
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="Email" class="form-label">Email: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    '.$email.'<br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="nic" class="form-label">NIC: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    '.$nic.'<br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="Tele" class="form-label">Telephone: </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                    '.$tele.'<br><br>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label-td" colspan="2">
                                        <label for="spec" class="form-label">Specialties: </label>
                                        
                                    </td>
                                </tr>
                                <tr>
                                <td class="label-td" colspan="2">
                                '.$spename.'<br><br>
                                </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <a href="doctors.php"><input type="button" value="OK" class="login-btn btn-primary-soft btn" ></a>
                                    </td>
                        
                                </tr>
                                

                            </table>
                            </div>
                        </center>
                        <br><br>
                    </div>
                    </div>
                ';  
        }elseif($action=='add'){
                $error_msg=$_GET["error"];
                $errorlist = array(
                    '1' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Already have an account for this Email address.</label>',
                    '2' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Password Conformation Error! Check Password again.</label>',
                    '3' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;"></label>',
                    '4' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">NIC already exists.</label>',
                    '5' => '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Specialty is required.</label>'
                );
                
                if (isset($errorlist[$error_msg])){
                    $display_error = $errorlist[$error_msg];
                }else{
                    $display_error = $errorlist['3'];
                }
                
                echo '
                <div id="popup1" class="overlay">
                        <div class="popup">
                        <center>
                        
                            <a class="close" href="doctors.php">&times;</a> 
                            <div class="content">
                                '.$display_error.'
                                
                            </div>
                            <div style="display: flex;justify-content: center;">
                            <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                            <tr>
                                <td class="label-td" colspan="2">'.
                                    $display_error.
                                '</td>
                            </tr>

                            <tr>
                                <td>
                                    <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Add New Doctor.</p><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <form action="add-new.php" method="POST" class="add-new-form">
                                    <label for="name" class="form-label">Name: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="name" class="input-text" placeholder="Doctor Name" required><br>
                                </td>
                                
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Email" class="form-label">Email: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="email" name="email" class="input-text" placeholder="Email Address" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="nic" class="form-label">NIC: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="text" name="nic" class="input-text" placeholder="NIC Number" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="Tele" class="form-label">Telephone: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="tel" name="tele" class="input-text" placeholder="Telephone Number" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="spec" class="form-label">Choose Specialties: (Name)</label>
                                    
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <select name="spec" id="" class="box" required>
                                    <option value="" disabled selected hidden>Choose Specialty Name</option>';
                                        
                                        $list11 = $database->query("select * from specialties order by sname asc;");

                                        for ($y=0;$y<$list11->num_rows;$y++){
                                            $row00=$list11->fetch_assoc();
                                            $sn=$row00["sname"];
                                            $id00=$row00["id"];
                                            echo "<option value='".$sn."'>$sn</option><br/>"; // Changed value to sname (Name)
                                        };
                                        
                        echo '      </select><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="password" class="form-label">Password: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="newpassword" class="input-text" placeholder="Defin a Password" required><br>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <label for="cpassword" class="form-label">Confirm Password: </label>
                                </td>
                            </tr>
                            <tr>
                                <td class="label-td" colspan="2">
                                    <input type="password" name="cpassword" class="input-text" placeholder="Confirm Password" required><br>
                                </td>
                            </tr>
                            
                
                            <tr>
                                <td colspan="2">
                                    <input type="reset" value="Clear" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                
                                    <input type="submit" value="Add" class="login-btn btn-primary btn">
                                </td>
                
                            </tr>
                           
                            </form>
                            </tr>
                        </table>
                        </div>
                        </center>
                        </div>
                    </div>
                ';
        }elseif($action=='edit'){
            $sqlmain= "select * from doctor where docid='$id'";
            $result= $database->query($sqlmain);
            $row=$result->fetch_assoc();
            $name=$row["docname"];
            $email=$row["docemail"];
            $speid=$row["specialties"];
            
            $nic=$row['docnic'];
            $tele=$row['doctel'];

            $error_msg=$_GET["error"];
            $errorlist = array(
                '1'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Already have an account for this Email address.</label>',
                '2'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Password Conformation Error! Check Password again.</label>',
                '3'=>'',
                '4'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">NIC already exists.</label>',
                '5'=>'<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Specialty is required.</label>'
            );
            
            if (isset($errorlist[$error_msg])){
                $display_error = $errorlist[$error_msg];
            }else{
                $display_error = $errorlist['3'];
            }
            
            // Fetch the currently selected specialty name
            $spesql = "select sname from specialties where id='$speid'";
            $speres = $database->query($spesql);
            $spefetch = $speres->fetch_assoc();
            $current_spename = $spefetch["sname"];


            echo '
            <div id="popup1" class="overlay">
                    <div class="popup">
                    <center>
                    
                        <a class="close" href="doctors.php">&times;</a> 
                        <div class="content">
                            '.$display_error.'
                            
                        </div>
                        <div style="display: flex;justify-content: center;">
                        <table width="80%" class="sub-table scrolldown add-doc-form-container" border="0">
                        <tr>
                            <td class="label-td" colspan="2">
                                '. $display_error.'
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="padding: 0;margin: 0;text-align: left;font-size: 25px;font-weight: 500;">Edit Doctor Details.</p>
                                Doctor ID : '.$id.'  (Auto Generated)<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <form action="edit-doc.php" method="POST" class="add-new-form">
                                <input type="hidden" value="'.$id.'" name="id00">
                                <label for="name" class="form-label">Name: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="text" name="name" class="input-text" placeholder="Doctor Name" value="'.$name.'" required><br>
                            </td>
                            
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Email" class="form-label">Email: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="email" name="email" class="input-text" placeholder="Email Address" value="'.$email.'" required><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="nic" class="form-label">NIC: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="text" name="nic" class="input-text" placeholder="NIC Number" value="'.$nic.'" required><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="Tele" class="form-label">Telephone: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="tel" name="tele" class="input-text" placeholder="Telephone Number"  value="'.$tele.'" required><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="spec" class="form-label">Choose Specialties: (Name)</label>
                                
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <select name="spec" id="" class="box" required>
                                <option value="'.$current_spename.'" selected hidden>'.$current_spename.' (Current)</option>'; // Display current name
                                    
                                    $list11 = $database->query("select * from specialties order by sname asc;");

                                    for ($y=0;$y<$list11->num_rows;$y++){
                                        $row00=$list11->fetch_assoc();
                                        $sn=$row00["sname"];
                                        echo "<option value='".$sn."'>$sn</option><br/>"; // Changed value to sname (Name)
                                    };
                                    
                    echo '      </select><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="password" class="form-label">Password: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="password" name="newpassword" class="input-text" placeholder="New Password"><br>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <label for="cpassword" class="form-label">Confirm Password: </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-td" colspan="2">
                                <input type="password" name="cpassword" class="input-text" placeholder="Confirm New Password"><br>
                            </td>
                        </tr>
                        
            
                        <tr>
                            <td colspan="2">
                                <input type="reset" value="Reset" class="login-btn btn-primary-soft btn" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            
                                <input type="submit" value="Save" class="login-btn btn-primary btn">
                            </td>
            
                        </tr>
                       
                        </form>
                        </tr>
                    </table>
                    </div>
                    </center>
                    </div>
                </div>
            ';
        }
    }
    ?>
</body>
</html>