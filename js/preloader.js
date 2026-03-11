document.addEventListener("DOMContentLoaded", function () {
  const preloader = document.getElementById("preloader");
  const loaderContainer = document.getElementById("loader-animation");

  let preloaderTimer;

  preloaderTimer = setTimeout(() => {
    preloader.classList.add("show");

    lottie.loadAnimation({
      container: loaderContainer,
      renderer: "svg",
      loop: true,
      autoplay: true,
      path: "./extra/preloader.json",
    });
  }, 200); // время ожидания

  window.addEventListener("load", function () {
    clearTimeout(preloaderTimer);
    preloader.classList.remove("show");
  });
});
