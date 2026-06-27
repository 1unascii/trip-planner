<script src="js/jquery/jquery-sidebar/js/sidebar.js" defer></script>
<script>
$(document).ready(function() {
  var leftSidebar = new Sidebar('#sidebar', '#open-left', 'left', '350px', 300);
  leftSidebar.init();

  var rightSidebar = new Sidebar('#sidebar-right', '#open-right', 'right', '300px', 300);
  rightSidebar.init();
});
</script>

</html>
