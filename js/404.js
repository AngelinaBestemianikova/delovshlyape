document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("animation404");

  if (container) {
    lottie.loadAnimation({
      container: container,
      renderer: "svg",
      loop: true,
      autoplay: true,
      path: "./extra/404.json",
    });
  }
});
