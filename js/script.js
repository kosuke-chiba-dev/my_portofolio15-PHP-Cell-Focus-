(function () {
  const userbar = document.querySelector('.header-right');
  if (!userbar) return;

  const icon = userbar.querySelector('.current_user_icon');
  const menu = userbar.querySelector('.toggleArea');
  if (!icon || !menu) return;

  document.addEventListener('click', function (e) {
    const onIcon = e.target.closest('.current_user_icon');
    const onMenu = e.target.closest('.toggleArea');

    if (onIcon === icon) {
      icon.classList.toggle('active');
      return;
    }

    if (onMenu && userbar.contains(onMenu)) {
      return;
    }

    icon.classList.remove('active');
  });
})();


