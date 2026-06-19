<?php
// Page temporaire pour poster la sélection d'une année via POST en utilisant la session du navigateur
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Post select annee</title></head>
<body>
<form id="f" method="POST" action="index.php?action=changer_annee">
  <input type="hidden" name="action_type" value="select">
  <input type="hidden" name="id_annee" value="1">
</form>
<script>document.getElementById('f').submit();</script>
</body>
</html>