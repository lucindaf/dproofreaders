<?php
  function get_biography($id) {
    $result = mysqli_query(DPDatabase::get_connection(), "SELECT bio FROM biographies WHERE bio_id = $id;");
    $row = mysqli_fetch_assoc($result);
    if (!$row)
    {
      // not found!
      return sprintf(_("An error has occurred somewhere. The project manager of this project has requested that a biography be automatically inserted here, but no biography is known by the id provided: <b>%s</b>.<br><br>You may wish to contact the project manager so that the problem can be resolved."),$id);
    }
    else
    {
      $bio_text = $row["bio"];
      // wrap in commentaries and return
      return "<!-- begin bio retrieved from database: id=$id -->\n$bio_text\n<!-- end bio retrieved from database: id=$id -->\n";
    }
  }
?>
