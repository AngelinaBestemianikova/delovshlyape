<form action="./logout.php" method="post">
    <input type="submit" value="Выйти" class="buttonlogout" onclick="confirmLogout()">
</form>
<script language='Javascript' type='text/javascript'>
    function confirmLogout() {
        alert(`Вы вышли из аккаунта!`);
        reload();
    }
</script>
