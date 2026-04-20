</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
    document.getElementById('sidebarClose').style.display = 'inline';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}
// Close sidebar on any nav link click (mobile)
document.querySelectorAll('.sidebar nav a').forEach(function(a) {
    a.addEventListener('click', closeSidebar);
});
// Close sidebar on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
</body>
</html>
