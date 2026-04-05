(function () {
  var track = document.getElementById("carouselTrack");
  var dotsWrap = document.getElementById("carouselDots");
  var slides = [];
  var index = 0;
  var timer;

  var carouselReady = false;
  var navReady = false;
  var $loader = document.getElementById("pageLoader");
  var $carouselWrap = document.getElementById("carouselWrap");
  var $navSkeleton = document.getElementById("navSkeleton");

  function hideLoader() {
    if (carouselReady && navReady) {
      if ($loader) $loader.classList.add("hidden");
      setTimeout(function() {
        if ($loader && $loader.parentNode) $loader.parentNode.removeChild($loader);
      }, 450);
    }
  }

  function resolveImageUrl(u) {
    if (!u) return "";
    if (/^https?:\/\//i.test(u) || (u.length >= 2 && u.substr(0, 2) === "//")) return u;
    if (u.charAt(0) === "/") return u;
    return "/" + u.replace(/^\/+/, "");
  }

  function buildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = '';
    for (var i = 0; i < slides.length; i++) {
      var b = document.createElement("button");
      b.type = "button";
      b.setAttribute("aria-label", "第" + (i + 1) + "张");
      b.addEventListener("click", function (j) {
        return function () {
          goTo(j);
          startAuto();
        };
      }(i));
      dotsWrap.appendChild(b);
    }
  }

  function buildSlides() {
    if (!track) return;
    if ($carouselWrap) {
      var cs = $carouselWrap.querySelector(".carousel-skeleton");
      if (cs) cs.classList.remove("active");
    }
    track.innerHTML = '';
    for (var i = 0; i < slides.length; i++) {
      var div = document.createElement("div");
      div.className = "carousel-slide";
      var a = document.createElement("a");
      if (slides[i].link) {
        a.href = slides[i].link;
        a.target = "_blank";
        a.rel = "noopener noreferrer";
      }
      var img = document.createElement("img");
      img.src = resolveImageUrl(slides[i].image_url);
      img.alt = "";
      img.loading = "lazy";
      img.decoding = "async";
      img.onerror = function() {
        this.parentElement.style.display = 'none';
      };
      a.appendChild(img);
      div.appendChild(a);
      track.appendChild(div);
    }
    track.style.width = slides.length * 100 + "%";
    for (var s = 0; s < track.children.length; s++) {
      track.children[s].style.flexBasis = (100 / slides.length) + "%";
      track.children[s].style.width = (100 / slides.length) + "%";
    }
  }

  function goTo(i) {
    index = (i + slides.length) % slides.length;
    track.style.transform = "translateX(-" + (index * 100 / slides.length) + "%)";
    if (dotsWrap) {
      var dots = dotsWrap.querySelectorAll("button");
      for (var d = 0; d < dots.length; d++) {
        dots[d].setAttribute("aria-current", d === index ? "true" : "false");
      }
    }
  }

  function next() {
    goTo(index + 1);
  }

  function startAuto() {
    stopAuto();
    timer = setInterval(next, 4500);
  }

  function stopAuto() {
    if (timer) clearInterval(timer);
  }

  // 加载轮播图
  if ($carouselWrap) {
    var cs = $carouselWrap.querySelector(".carousel-skeleton");
    if (cs) cs.classList.add("active");
  }

  fetch("api/carousel.php")
    .then(function (res) { return res.json(); })
    .then(function (data) {
      carouselReady = true;
      if ($carouselWrap) {
        var cs = $carouselWrap.querySelector(".carousel-skeleton");
        if (cs) cs.classList.remove("active");
      }
      if (data && data.length) {
        slides = data;
        buildDots();
        buildSlides();
        goTo(0);
        startAuto();
        var wrap = track.parentElement;
        wrap.addEventListener("mouseenter", stopAuto);
        wrap.addEventListener("mouseleave", startAuto);
      }
      hideLoader();
    })
    .catch(function () {
      carouselReady = true;
      if ($carouselWrap) {
        var cs = $carouselWrap.querySelector(".carousel-skeleton");
        if (cs) cs.classList.remove("active");
      }
      hideLoader();
    });

  var navItems = {};
  var listEl = document.getElementById("navList");
  var tabs = document.querySelectorAll(".tab");

  function render(cat) {
    var items = navItems[cat] || [];
    listEl.innerHTML = "";
    if (!items.length) {
      listEl.innerHTML = '<p class="empty-hint">暂无内容</p>';
      return;
    }
    var ul = document.createElement("ul");
    ul.className = "nav-grid";
    for (var k = 0; k < items.length; k++) {
      var it = items[k];
      var li = document.createElement("li");
      var a = document.createElement("a");
      a.className = "nav-card";
      a.href = it.href;
      a.target = "_blank";
      a.rel = "noopener noreferrer";
      var img = document.createElement("img");
      img.className = "nav-card-icon";
      img.src = it.icon;
      img.alt = "";
      img.loading = "lazy";
      img.decoding = "async";
      var body = document.createElement("div");
      body.className = "nav-card-body";
      var t = document.createElement("div");
      t.className = "nav-card-title";
      t.textContent = it.title;
      var s = document.createElement("div");
      s.className = "nav-card-sub";
      s.textContent = it.sub;
      body.appendChild(t);
      body.appendChild(s);
      a.appendChild(img);
      a.appendChild(body);
      li.appendChild(a);
      ul.appendChild(li);
    }
    listEl.appendChild(ul);
  }

  function setTab(cat) {
    for (var t = 0; t < tabs.length; t++) {
      tabs[t].classList.toggle("is-active", tabs[t].dataset.cat === cat);
    }
    render(cat);
  }

  for (var x = 0; x < tabs.length; x++) {
    tabs[x].addEventListener("click", function () {
      setTab(this.dataset.cat);
    });
  }

  fetch("api/nav.php")
    .then(function (res) { return res.json(); })
    .then(function (data) {
      navReady = true;
      if ($navSkeleton) $navSkeleton.style.display = "none";
      if (data && typeof data === "object") {
        navItems = data;
        var firstCat = tabs.length > 0 ? tabs[0].dataset.cat : "market";
        setTab(firstCat);
      }
      hideLoader();
    })
    .catch(function () {
      navReady = true;
      if ($navSkeleton) $navSkeleton.style.display = "none";
      var listEl = document.getElementById("navList");
      if (listEl) listEl.innerHTML = '<p class="empty-hint">加载失败，请刷新重试</p>';
      hideLoader();
    });
})();

function shareSite() {
  var url = window.location.href;
  var title = document.title;
  var ua = navigator.userAgent.toLowerCase();
  if (ua.indexOf('micromessenger') > -1) {
    var tip = document.createElement('div');
    tip.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.8);color:#fff;padding:20px 28px;border-radius:12px;font-size:14px;z-index:9999;text-align:center;line-height:1.6;';
    tip.innerHTML = '请点击右上角<br><strong>···</strong> 菜单<br>选择「发送给朋友」或「分享到朋友圈」';
    document.body.appendChild(tip);
    tip.onclick = function() { document.body.removeChild(tip); };
    setTimeout(function() { if (tip.parentNode) document.body.removeChild(tip); }, 4000);
  } else if (navigator.share) {
    navigator.share({ title: title, url: url }).catch(function() {});
  } else {
    var copy = document.createElement('input');
    copy.value = url;
    document.body.appendChild(copy);
    copy.select();
    document.execCommand('copy');
    document.body.removeChild(copy);
    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.75);color:#fff;padding:12px 20px;border-radius:8px;font-size:14px;z-index:9999;';
    toast.textContent = '链接已复制到剪贴板';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) document.body.removeChild(toast); }, 2500);
  }
}
