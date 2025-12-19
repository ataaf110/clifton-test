/**
 * clifton Test Script (Intrinsic / Extrinsic / General Satisfaction)
 * ساختار صفحات و جریان آزمون عیناً حفظ شده؛ فقط محتوا و محاسبه‌ها clifton است.
 * @version 2.0.0
 */

console.log("clifton Script Loaded");

// ===== حالت‌ها =====
let questions = [];
let answers = [];
let currentQuestion = 0;
let selectedOption = null;
let selectedTestOption = "free";
let userInfo = {};
let charts = {};
let chatInitialized = false; // رزرو آینده

let degrees = {
            "achiever"          : 0,
            "activator"         : 0,
            "adaptability"      : 0,
            "analytical"        : 0,
            "arranger"          : 0,
            "belief"            : 0,
            "command"           : 0,
            "communication"     : 0,
            "competition"       : 0,
            "connectedness"     : 0,
            "consistency"       : 0,
            "deliberative"      : 0,
            "discipline"        : 0,
            "focus"             : 0,
            "responsibility"    : 0,
            "restorative"       : 0,
            "empathy"           : 0,
            "harmony"           : 0,
            "includer"          : 0,
            "individualization" : 0,
            "developer"         : 0,
            "positivity"        : 0,
            "relator"           : 0,
            "futuristic"        : 0,
            "ideation"          : 0,
            "input"             : 0,
            "intellection"      : 0,
            "learner"           : 0,
            "strategic"         : 0,
            "context"           : 0,
            "self_assurance"    : 0,
            "significance"      : 0,
            "woo"               : 0,
            "maximizer"         : 0
        };

// ===== المنت‌ها =====
const startPage = document.getElementById("start-page");
const testPage = document.getElementById("test-page");
const preparingPage = document.getElementById("preparing-page");
const resultsPage = document.getElementById("results-page");
const questionText1 = document.getElementById("question-text-1");
const questionText2 = document.getElementById("question-text-2");
const progressText = document.getElementById("progress-text");
const progressPercent = document.getElementById("progress-percent");
const progressFill = document.getElementById("progress-fill");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");
const showResultsBtn = document.getElementById("show-results-btn");
const birthYearSelect = document.getElementById("birth-year");

// API bridge (cliftonAjax توسط clifton-bridge مقداردهی می‌شود)
const cliftonChatbot = {
  ajaxurl: "https://elm-angize.ir/personality/clifton/api/clifton_api.php",  // FIXME
};

// ===== سال تولد =====
if (birthYearSelect) {
  for (let year = 1404; year >= 1320; year--) {
    const opt = document.createElement("option");
    opt.value = year;
    opt.textContent = year;
    birthYearSelect.appendChild(opt);
  }
}

// ===== ابزار =====
function validateMobileNumber(m) {
  const c = (m || "").replace(/[^0-9]/g, "");
  return /^[0-9]{10,11}$/.test(c);
}
function checkcliftonAjax() {
  if (typeof cliftonAjax === "undefined" || !cliftonAjax.ajax_url || !cliftonAjax.nonce) {
    console.error("cliftonAjax is not defined or incomplete");
    alert("خطا در تنظیمات آزمون. لطفاً با پشتیبانی تماس بگیرید.");
    return false;
  }
  return true;
}

// ===== لود سوالات =====
function loadQuestions() {
  if (!checkcliftonAjax()) return;
  jQuery.ajax({
    url: cliftonAjax.ajax_url,
    type: "POST",
    data: { action: "get_clifton_questions", nonce: cliftonAjax.nonce },
    success: function (res) {
      if (res.success) {
        questions = res.data.questions || [];
      } else {
        alert("خطا در بارگذاری سوالات: " + res.data);
      }
    },
    error: function (_) {
      alert("خطا در ارتباط با سرور. لطفاً دوباره امتحان کنید.");
    },
  });
}

// ===== انتخاب پلن =====
function selectOption(option) {
  selectedTestOption = option;
  document.querySelectorAll(".border-2").forEach((el) => {
    el.classList.remove("border-primary-400", "border-secondary-400");
    el.classList.add("border-gray-200");
  });
  const card = document.querySelector(`[onclick="selectOption('${option}')"]`);
  card.classList.remove("border-gray-200");
  card.classList.add(
    option === "free" ? "border-primary-400" : "border-secondary-400"
  );
}

// ===== شروع =====
function startTest() {
  const birthYear = birthYearSelect?.value;
  const genderEl = document.querySelector('input[name="gender"]:checked');

  if (!birthYear || birthYear === "انتخاب کنید")
    return alert("لطفاً سال تولد را انتخاب کنید");
  if (!genderEl) return alert("لطفاً جنسیت را انتخاب کنید");
  if (!questions.length) {
    alert("سوالات لود نشده‌اند.");
    loadQuestions();
    return;
  }

  userInfo = {
    birthYear: parseInt(birthYear),
    gender: genderEl.value === "male" ? "مرد" : "زن",
    date: new Date().toLocaleDateString("fa-IR"),
  };
  answers = [];
  currentQuestion = 0;
  selectedOption = null;

  startPage.classList.add("animate__fadeOut");
  setTimeout(() => {
    startPage.classList.add("hidden");
    startPage.classList.remove("animate__fadeOut");
    testPage.classList.remove("hidden");
    testPage.classList.add("animate__fadeIn");
    updateQuestion();
  }, 400);
}

// ===== آپدیت سوال =====
function updateQuestion() {
  if (currentQuestion >= questions.length) return;

  const exp1 = questions[currentQuestion][0];
  const exp2 = questions[currentQuestion][1];
  
  questionText1.textContent = exp1.text;
  questionText2.textContent = exp2.text;

  progressText.textContent = `سوال ${currentQuestion + 1} از ${questions.length}`;
  const p = Math.round(((currentQuestion + 1) / questions.length) * 100);  // Progress Percentage value
  progressPercent.textContent = `${p}%`;
  progressFill.style.width = `${p}%`;

  prevBtn.disabled = currentQuestion === 0;
  prevBtn.classList.toggle("opacity-50", prevBtn.disabled);
  prevBtn.classList.toggle("cursor-not-allowed", prevBtn.disabled);

  nextBtn.disabled = true;
  nextBtn.classList.add("opacity-50", "cursor-not-allowed");
  nextBtn.innerHTML =
    currentQuestion === questions.length - 1
      ? 'اتمام آزمون <i class="fas fa-check mr-2"></i>'
      : 'سوال بعدی <i class="fas fa-arrow-left mr-2"></i>';

  document.querySelectorAll(".option-btn").forEach(
    (btn) => btn.classList.remove('border-blue-300')
  );

  
  if (answers[currentQuestion]) {
    idx = answers[currentQuestion].answer;
    idx = document.querySelectorAll(".option-btn")[idx-1];
    idx?.classList.add('border-blue-300');
    selectedOption = answers[currentQuestion];
    nextBtn.disabled = false;
    nextBtn.classList.remove("opacity-50", "cursor-not-allowed");
  } else {
    selectedOption = null;
  }
}

// ===== انتخاب پاسخ =====
function selectAnswer(e, ans) {
  desiredPhrase = 
    ans < 3 ? 0 :
    ans === 3 ? null :
    1;

  degree = 
    desiredPhrase === 0 ? ans :
    desiredPhrase === 1 ? ans - 3 :
    0;

  selectedOption = {
    'answer': ans,
    'themes' : desiredPhrase !== null ? questions[currentQuestion][desiredPhrase]['themes'] : null,
    'degree' : degree
  };

  document
    .querySelectorAll(".option-btn")
    .forEach((b) =>
      b.classList.remove('border-blue-300')
    );
  e.target.classList.add('border-blue-300');
  nextBtn.disabled = false;
  nextBtn.classList.remove("opacity-50", "cursor-not-allowed");
  setTimeout(nextQuestion, 1);
}

function nextQuestion() {
  if (selectedOption === null && currentQuestion >= answers.length)
    return alert("لطفاً یک گزینه را انتخاب کنید");
  if (currentQuestion >= answers.length) answers.push(selectedOption);
  else answers[currentQuestion] = selectedOption;

  selectedOption = null;
  if (currentQuestion === questions.length - 1) return completeTest();
  currentQuestion++;
  updateQuestion();
}
function prevQuestion() {
  if (currentQuestion > 0) {
    currentQuestion--;
    updateQuestion();
  }
}

// ===== تکمیل =====
function completeTest() {
  if (answers.length < questions.length)
    return alert("به همه‌ی سوالات پاسخ دهید.");

  testPage.classList.add("animate__fadeOut");
  setTimeout(() => {
    testPage.classList.add("hidden");
    testPage.classList.remove("animate__fadeOut");
    preparingPage.classList.remove("hidden");
    preparingPage.classList.add("animate__fadeIn");
    setTimeout(() => {
      showResultsBtn.classList.remove("opacity-0");
      showResultsBtn.classList.add("opacity-100");
    }, 1800);
  }, 400);
}

// ===== ذخیره در سرور =====
function showResults() {
  if (!checkcliftonAjax()) return;
  const err = document.getElementById("mobile-error");
  if (err) err.classList.add("hidden");
  const input = document.getElementById("mobile-number");
  if (!input) return alert("فیلد موبایل یافت نشد.");

  const m = (input.value || "").trim();
  if (!validateMobileNumber(m)) {
    if (err) {
      err.textContent = "لطفاً شماره موبایل معتبر وارد کنید.";
      err.classList.remove("hidden");
    } else alert("شماره موبایل معتبر نیست.");
    input.focus();
    return;
  }
  saveResultsToServer(m);
}

function saveResultsToServer(mobile) {
  const results = calculateResults();
  const cleaned = mobile.replace(/^(\+98|0)/, "");
  userInfo.mobile_number = cleaned;

  jQuery.ajax({
    url: cliftonAjax.ajax_url,
    type: "POST",
    data: {
      action: "save_clifton_results",
      nonce: cliftonAjax.nonce,
      mobile_number: cleaned,
      user_info: JSON.stringify(userInfo),
      degrees: JSON.stringify(degrees),
      test_option: selectedTestOption,
    },
    success: function (resp) {
      if (resp && resp.success) {
        const token = resp.data?.token || "";
        try {
          if (token) {
            sessionStorage.setItem("clifton_token", token);
            localStorage.setItem("clifton_token", token);
          }
          localStorage.setItem("cliftonMobile", cleaned);
          localStorage.setItem(
            "cliftonResults",
            JSON.stringify({
              degrees: results.degrees,
              userInfo: userInfo,
              selectedTestOption: selectedTestOption,
            })
          );
        } catch (e) { }
        preparingPage.classList.add("animate__fadeOut");
        setTimeout(() => {
          preparingPage.classList.add("hidden");
          preparingPage.classList.remove("animate__fadeOut");
          if (token)
            window.location.href =
              cliftonAjax.results_page_url + "?m=" + encodeURIComponent(cleaned);
          else
            window.location.href =
              cliftonAjax.results_page_url + "?m=" + encodeURIComponent(cleaned);
        }, 400);
      } else {
        const err = document.getElementById("mobile-error");
        if (err) {
          err.textContent = "خطا در ذخیره نتایج: " + (resp && resp.data);
          err.classList.remove("hidden");
        } else alert("خطا در ذخیره نتایج.");
      }
    },
    error: function () {
      alert("خطا در ارتباط با سرور.");
    },
  });
}

// ===== محاسبه نتایج clifton =====
function calculateResults() {
  if (!Array.isArray(answers) || answers.length !== questions.length)
    throw new Error("پاسخ‌ها کامل نیست.");

  answers.forEach( function (answer) {

    if (answer.themes){
      answer.themes.forEach( function (themeData) {
        degreeToBeSummed = themeData.weight * answer.degree;
        degrees[themeData['theme']] = (degrees[themeData['theme']] || 0) + degreeToBeSummed;
        }
      );
    }
  });

  // برای ذخیره/ارسال
  try {
    localStorage.setItem(
      "cliftonResults",
      JSON.stringify({
        degrees,
        userInfo,
        selectedTestOption,
      })
    );
  } catch (e) { }

  return {
    degrees,
  };
}

// ===== نمایش نتایج (results.php) =====
function displayResults() {
  if (!checkcliftonAjax()) return;

  const qs = new URLSearchParams(window.location.search);
  const tokenParam = qs.get("t");
  const ridParam = qs.get("rid");
  const mobileStored = localStorage.getItem("cliftonMobile");

  const path = window.location.pathname;
  const isTest = path.split('/').filter(Boolean).pop() === "results-test.php"; 
  if (isTest) {
    data = { action: "get_clifton_results", mobile_number: "test" };
  } else {
    data = { action: "get_clifton_results", nonce: cliftonAjax.nonce };
    let token = tokenParam || "";
    if (!token) {
      try {
        token =
          sessionStorage.getItem("clifton_token") ||
          localStorage.getItem("clifton_token") ||
          "";
      } catch (e) { }
    }
    if (token) data.t = token;
    else if (ridParam) data.rid = ridParam;
    else if (mobileStored) data.mobile_number = mobileStored;
  }

  jQuery.ajax({
    url: cliftonAjax.ajax_url,
    type: "POST",
    data,
    success: function (resp) {
      console.log('ajax request was successfull');
      if (resp.success && resp.data?.results) {
        const r = resp.data.results;
        const stored = JSON.parse(localStorage.getItem("cliftonResults") || "{}");
        const merged = {input:{
            degrees: r.input.degrees || stored.input.degrees || {},
            userInfo: r.input.user_info ||
              stored.input.userInfo || {
              date: "نامشخص",
              birthYear: 1400,
              gender: "نامشخص",
            },
            selectedTestOption:
              r.input.test_option || stored.input.selectedTestOption || "free",
          }, 
          themes: r.themes,
          domains: r.domains  
        };
        renderResults(merged);
      } else {
        console.log('ajax request failed!');
        const stored = JSON.parse(localStorage.getItem("cliftonResults") || "{}");
        if (stored.degrees) renderResults(stored);
        else {
          alert("نتایج یافت نشد. آزمون را دوباره انجام دهید.");
          window.location.href = cliftonAjax.test_page_url;
        }
      }
    },
    error: function () {
      console.log('error while fetching ajax request')
      const stored = JSON.parse(localStorage.getItem("cliftonResults") || "{}");
      if (stored.degrees) renderResults(stored);
      else {
        alert("خطا در ارتباط با سرور.");
        window.location.href = cliftonAjax.test_page_url;
      }
    },
  });
}

function renderResults(r) {
  const data = r;

  const helper = getHelper();

  const mostDominantThemeElement = document.querySelector('#summary-tab .placeholder-dominant-theme');
  const mostDominantDomainElement = document.querySelector('#summary-tab .placeholder-dominant-domain');
  const leastDominantThemeElement = document.querySelector('#summary-tab .placeholder-least-theme');

  const mostDominantTheme = helper.getMostDominantTheme(data.input.degrees);
  const mostDominantDomain = helper.getMostDominantDomain(data.input.degrees, data.themes);
  const leastDominantTheme = helper.getLeastDominantTheme(data.input.degrees);

  mostDominantThemeElement.textContent = `${data.themes[mostDominantTheme]['name']} | ${helper.formatter(mostDominantTheme)}`;
  mostDominantDomainElement.textContent = `${data.domains[mostDominantDomain]['name']} | ${helper.formatter(mostDominantDomain)}`;
  leastDominantThemeElement.textContent = `${data.themes[leastDominantTheme]['name']} | ${helper.formatter(leastDominantTheme)}`;;

  const firstFivethemesContainer = document.querySelector('#dominant-themes-tab .first-5-themes');
  const secondFiveThemesContainer = document.querySelector('#dominant-themes-tab .second-5-themes');
  const otherThemesContainer = document.querySelector('#dominant-themes-tab .other-themes');
  
  const sortedThemes = helper.getSortedThemes(data.input.degrees);

  const firstThemeData = data['themes'][sortedThemes[0]];

  const firstDominantTheme = firstFivethemesContainer.querySelector('.first-dominant-theme');
  firstDominantTheme.querySelector('.theme-title').innerHTML = firstThemeData['name'];
  firstDominantTheme.querySelector('.theme-title').classList.add(`text-${firstThemeData['domain']}-primary`);
  firstDominantTheme.querySelector('.theme-icon').innerHTML = firstThemeData['icon'];
  firstDominantTheme.querySelector('.theme-icon').classList.add(`text-${firstThemeData['domain']}-primary`);
  firstDominantTheme.querySelector('.theme-id-phrase').innerHTML = firstThemeData['phrase'];
  firstDominantTheme.querySelector('.theme-id-phrase').classList.add(`text-${firstThemeData['domain']}-sep`);
  firstDominantTheme.querySelector('.theme-rank').classList.add(`bg-${firstThemeData['domain']}-primary`);


  for (let i = 2; i <= 34; i++) {
    const themeName = sortedThemes[i-1];
    const themeData = data['themes'][themeName];

    let container;
    let cardIndex;

    if (i <= 5) {
        container = firstFivethemesContainer;
        cardIndex = i - 1;
    }
    else if (i <= 10) {
        container = secondFiveThemesContainer;
        cardIndex = i - 5; 
    }
    else {
        container = otherThemesContainer;
        cardIndex = i - 10;
    }

    const iconBox = container.querySelector(`.theme-card:nth-child(${cardIndex})`);

    if (!iconBox) continue;

    const domain = themeData['domain'];

    iconBox.querySelector('.theme-title').innerHTML = themeData['name'];
    iconBox.querySelector('.theme-title').classList.add(`text-${domain}-primary`);

    iconBox.querySelector('.theme-icon').innerHTML = themeData['icon'];
    iconBox.querySelector('.theme-icon').classList.add(`text-${domain}-primary`);
    
    if (i <= 5) {
      iconBox.querySelector('.theme-rank').classList.add(`bg-${domain}-primary`);
    } else if (i <= 10) {
      iconBox.querySelector('.theme-rank').innerHTML = `#${i}`;
      iconBox.querySelector('.theme-rank').classList.add(`text-${domain}-sep`);
      iconBox.querySelector('.theme-icon').parentElement.classList.add(`bg-${domain}-bg`);
    } else {
      iconBox.querySelector('.theme-rank').innerHTML = `${i}.`;
      iconBox.querySelector('.theme-rank').classList.add(`text-${domain}-sep`);
    }

    if (i <= 10) {
      iconBox.querySelector('.theme-id-phrase').innerHTML = themeData['phrase'];
      iconBox.querySelector('.theme-id-phrase').classList.add(`text-${domain}-sep`);
    }
  } 

  const sortedDomains = helper.getSortedDomains(data.input.degrees, data.themes);

  const domainSections = document.querySelectorAll("#domain-dominance-tab .main-section .domain-section");

  let h = 0;
  domainSections.forEach(function (section) {
    const domain = sortedDomains[h][0];
    const domainDegree = sortedDomains[h][1];

    // Manipulate Domain Column

    const iconElement = section.querySelector('.domain-col .domain-icon');
    const nameElement = section.querySelector('.domain-col .domain-name');
    const descriptionElement = section.querySelector('.domain-col .domain-description');
    const percentageElement = section.querySelector('.domain-col .domain-percentage');
    const chartElement = section.querySelector('.domain-col .domain-chart');
    const chartStroke = section.querySelector('.domain-col .domain-chart-stroke')

    const dominancePercentage = Math.round(100 * (domainDegree / data.domains[domain].maxDegree));
    const percentageLevel = 
      dominancePercentage < 30 ? 'low' :
      dominancePercentage < 60 ? 'average' : 'high';

    const description = data.domains[domain].descriptions[percentageLevel];

    iconElement.textContent = data.domains[domain].icon;
    iconElement.classList.add(`text-${domain}-primary`);
    iconElement.parentElement.classList.add(`bg-${domain}-bg`);

    nameElement.textContent = data.domains[domain].name;
    nameElement.classList.add(`text-${domain}-primary`);

    percentageElement.textContent = `${dominancePercentage}%`;
    descriptionElement.innerHTML = description;

    const r = Math.round((100 - dominancePercentage) / 100);
    chartElement['stroke-dashoffset'] = Math.round(r * chartElement['stroke-dasharray'], 2);
    chartElement.classList.add(`stroke-${domain}-primary`);
    chartStroke.classList.add(`stroke-white`);
    chartStroke.classList.add(`shadow-sm`);

    // Manipulate Themes Column

    const container = section.querySelector('.themes-col .themes-container');
    const themeRowTemplate = container.querySelector('.theme-row');
    container.innerHTML = '';

    const domainThemes = Object.keys(data.themes).filter((theme) => data.themes[theme].domain == domain);

    domainThemes.forEach(function (theme) {
      const row = themeRowTemplate.cloneNode(true);
      const themeData = data.themes[theme];

      const nameElement = row.querySelector('.theme-name');
      const chartElement = row.querySelector('.theme-chart');
      const percentageElement = row.querySelector('.theme-percentage');

      nameElement.textContent = themeData.name;
      
      const themeDegree = data.input.degrees[theme];
      const themeMaxDegree = themeData.maxDegree;
      const dominancePercentage = Math.round(100 * themeDegree / themeMaxDegree);

      themeData.dominancePercentage = dominancePercentage;
      
      chartElement.classList.add(`w-[${dominancePercentage}%]`);
      chartElement.classList.add(`bg-${themeData.domain}-primary`);
      chartElement.parentElement.classList.add(`bg-white`);

      percentageElement.textContent = `${dominancePercentage}%`;

      container.appendChild(row);

    });
    h++;
  })

  /////////////////////////////////////////////
  
  let dominantThemeTabTemplate;

  for (i=0; i<5; i++) {
    const dominantTheme = sortedThemes[i];

    const themeTab = document.getElementById(`dominant-theme-${i+1}-tab`);
    dominantThemeTabTemplate = dominantThemeTabTemplate || themeTab.cloneNode(true);
    const themeData = data.themes[dominantTheme];

    if (i > 0) {
      themeTab.innerHTML = dominantThemeTabTemplate.innerHTML;
    }

    const tabHeader = themeTab.querySelector('.theme-intro-section .tab-header');
    tabHeader.textContent = i == 0 ? 'اولین استعداد برجسته شما' :
                            i == 1 ? 'دومین استعداد برجسته شما' :
                            i == 2 ? 'سومین استعداد برجسته شما' :
                            i == 4 ? 'چهارمین استعداد برجسته شما' : 'پنجمین استعداد برجسته شما';

    themeTab.querySelector('.theme-intro-section .theme-title').textContent = themeData.name;
    themeTab.querySelector('.theme-intro-section .theme-description').textContent = themeData.onDominanceDescription;
    const themeImageUrl = helper.getResourceUrl(`theme_illustrations.${themeData.picture}`);
    themeTab.querySelector('.theme-intro-section .theme-picture').style.backgroundImage = `url("${themeImageUrl}")`;

    const dominanceSection = themeTab.querySelector('.dominance-section');

    dominanceSection.querySelector('.cards .domain-card .text').textContent = data.domains[themeData.domain].name;
    dominanceSection.querySelector('.cards .english-name-card .text').textContent = helper.formatter(dominantTheme);
    dominanceSection.querySelector('.cards .rank-card .text').textContent = `#${i+1}`;

    dominanceSection.querySelector('.dominance-description').textContent = themeData.dominanceSupportingDetails;

    const primaryColor = data.domains[themeData.domain].colors.primary;
    const bgColor = data.domains[themeData.domain].colors.bg;
    const backgroundValue = `radial-gradient(closest-side, white 0%, white 75%, transparent 75%, transparent 100%), conic-gradient(${primaryColor} 0% ${themeData.dominancePercentage}%, ${bgColor} ${themeData.dominancePercentage}% 100%)`;
    dominanceSection.querySelector('.dominance-chart').style.background = backgroundValue;

    dominanceSection.querySelector('.dominance-percentage').textContent = `${themeData.dominancePercentage}%`;

    dominanceLevelElement = dominanceSection.querySelector('.dominance-level');
    dominanceLevelElement.textContent = ` غلبه ${helper.findDominanceLevel(themeData.dominancePercentage)}`;

    const colorReg = /^(text|bg)-[^-]+-(bg|primary|sep)$/;
    dominanceLevelElement.classList.forEach( (cls) => {
      if (colorReg.test(cls)) {
        dominanceLevelElement.classList.remove(cls);
      }
    });

    dominanceLevelElement.classList.add(`text-${themeData.domain}-primary`)

    const identifiersSection = themeTab.querySelector('.identifiers-section');
    const positiveIdentifiersContainer = identifiersSection.querySelector('.positives');
    const negativeIdentifiersContainer = identifiersSection.querySelector('.negatives');

    const positiveTemplate = positiveIdentifiersContainer.querySelector('.card');
    const negativeTemplate = negativeIdentifiersContainer.querySelector('.card');
    
    positiveIdentifiersContainer.innerHTML = '';
    negativeIdentifiersContainer.innerHTML = '';

    themeData.positiveIdentifiers.forEach( function (phrase) {
      var card = positiveTemplate.cloneNode(true);
      card.textContent = phrase;
      
      positiveIdentifiersContainer.appendChild(card);
    });

    themeData.negativeIdentifiers.forEach( function (phrase) {
      var card = negativeTemplate.cloneNode(true);
      card.textContent = phrase;
      
      negativeIdentifiersContainer.appendChild(card);
    });

    const themePowersSection = themeTab.querySelector('.theme-powers');
    const themePowerTemplate = themePowersSection.querySelector('.item');

    themePowersSection.innerHTML = '';

    themeData.howThemeHelpsYou.forEach( function (text) {
      var item = themePowerTemplate.cloneNode(true);
      item.querySelector('.text').textContent = text;
      themePowersSection.appendChild(item);
    });

    const behavioralProfileSection = themeTab.querySelector('.behavioral-profile');
    behavioralProfileSection.querySelector('.pressure .text').textContent = themeData.behavioralProfile['pressure'];
    behavioralProfileSection.querySelector('.decision .text').textContent = themeData.behavioralProfile.decisionMaking;
    behavioralProfileSection.querySelector('.motivation .text').textContent = themeData.behavioralProfile.motivation;
    behavioralProfileSection.querySelector('.learning .text').textContent = themeData.behavioralProfile.learning;

    const strengthsContainer = themeTab.querySelector('.workplace .strengths');
    const weaknessesContainer = themeTab.querySelector('.workplace .weaknesses');

    const strengthTemplate = strengthsContainer.querySelector('.item');
    const weaknessTemplate = weaknessesContainer.querySelector('.item');

    strengthsContainer.innerHTML = weaknessesContainer.innerHTML = '';

    themeData.workplace.strengths.forEach( function (strength) {
      var item = strengthTemplate.cloneNode(true);
      item.querySelector('.title').textContent = strength.title;
      item.querySelector('.description').textContent = strength.description;

      strengthsContainer.appendChild(item);
    });

    themeData.workplace.weaknesses.forEach( function (weakness) {
      var item = weaknessTemplate.cloneNode(true);
      item.querySelector('.title').textContent = weakness.title;
      item.querySelector('.description').textContent = weakness.description;

      weaknessesContainer.appendChild(item);
    });

    const improvementMethodsSection = themeTab.querySelector('.improvement-methods');
    const methodTemplate = improvementMethodsSection.querySelector('.item');

    improvementMethodsSection.innerHTML = '';

    themeData.improvementMethods.forEach( function (method) {
      var item = methodTemplate.cloneNode(true);
      item.querySelector('.title').textContent = method.title;
      item.querySelector('.description').innerHTML = method.description;

      improvementMethodsSection.appendChild(item);
    });

    const bookSuggestionSection = themeTab.querySelector('.books');
    const bookRowRightTemplate = bookSuggestionSection.querySelector('.item.image-right');
    const bookRowLeftTemplate = bookSuggestionSection.querySelector('.item.image-left');

    bookSuggestionSection.innerHTML = '';

    var c = 0;
    themeData.books.forEach( function (book) { 
      const template = c%2 == 0 ? bookRowRightTemplate.cloneNode(true) : bookRowLeftTemplate.cloneNode(true);

      template.querySelector('.book-name').textContent = book.bookTitle;
      template.querySelector('.book-author').textContent = book.bookAuthor;
      template.querySelector('.description').textContent = book.description;

      const imageUrl = helper.getResourceUrl(`books.${book.picture}`);
      template.querySelector('.picture').style.backgroundImage = `url("${imageUrl}")`;
      template.querySelector('.picture').style.backgroundSize = "contain";


      bookSuggestionSection.appendChild(template);

      c++;
    });
  }

  (function () {
  const secondaryThemesTab = document.getElementById('secondary-themes-tab');

  const themeCards = secondaryThemesTab.querySelectorAll('.themes-overview .card');
  const themeIntroSections = secondaryThemesTab.querySelectorAll('.theme-section');

  for (i=6; i<=10; i++) {
    const theme = sortedThemes[i-1];
    const themeData = data.themes[theme];
    const index = i - 6;

    themeCards[index].querySelector('.title').textContent = themeData.name;
    themeCards[index].querySelector('.title').classList.add(`text-${themeData.domain}-primary`);
    themeCards[index].querySelector('.icon').textContent = themeData.icon;
    themeCards[index].querySelector('.icon').classList.add(`text-${themeData.domain}-primary`);
    themeCards[index].querySelector('.icon').parentElement.classList.add(`bg-${themeData.domain}-bg`);
    themeCards[index].querySelector('.id-phrase').textContent = themeData.phrase;
    themeCards[index].querySelector('.id-phrase').classList.add(`text-${themeData.domain}-sep`);
    themeCards[index].querySelector('.rank').textContent = `#${i}`;
    themeCards[index].querySelector('.rank').classList.add(`text-${themeData.domain}-sep`)

    const themeSection = themeIntroSections[index];

    const themeCardSeparators = themeSection.querySelectorAll('.cards .sep');
    themeCardSeparators.forEach((sep) => sep.classList.add(`bg-${themeData.domain}-sep`));

    const themeCardTitles = themeSection.querySelectorAll('.cards .title');
    themeCardTitles.forEach((title) => title.classList.add(`text-${themeData.domain}-primary`));

    themeSection.querySelector('.cards .domain-card .text').textContent = data.domains[themeData.domain].name;
    themeSection.querySelector('.cards .domain-card .text').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.cards .english-name-card .text').textContent = helper.formatter(theme);
    themeSection.querySelector('.cards .english-name-card .text').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.cards .rank-card .text').textContent = `#${i}`;
    themeSection.querySelector('.cards .rank-card .text').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-icon').textContent = themeData.icon;
    themeSection.querySelector('.theme-icon').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-title').textContent = themeData.name;
    themeSection.querySelector('.theme-title').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-chart').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.theme-chart').setAttribute('stroke-dasharray', `${themeData.dominancePercentage}, 100`);

    themeSection.querySelector('.theme-percentage').textContent = `${themeData.dominancePercentage}%`;
    themeSection.querySelector('.theme-percentage').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-description').textContent = themeData.onDominanceDescription;

    const themeUsagesContainer = themeSection.querySelector('.theme-usages');
    const usageTemplate = themeUsagesContainer.querySelector('.item');
    themeUsagesContainer.innerHTML = '';

    themeData.howThemeHelpsYou.forEach( function (usage) {
      const item = usageTemplate.cloneNode(true);
      item.querySelector('.text').textContent = usage;
      
      themeUsagesContainer.appendChild(item);
    });
  }}) ();

  (function () {
    const weaknessesTab = document.getElementById('weaknesses-tab');

    const themeCards = weaknessesTab.querySelectorAll('.themes-overview .card');
    const themeIntroSections = weaknessesTab.querySelectorAll('.theme-section');

    for (i=34; i>=30; i--) {
    const theme = sortedThemes[i-1];
    const themeData = data.themes[theme];
    const index = 4 - (i - 30);

    themeCards[index].querySelector('.title').textContent = themeData.name;
    themeCards[index].querySelector('.title').classList.add(`text-${themeData.domain}-primary`);
    themeCards[index].querySelector('.icon').textContent = themeData.icon;
    themeCards[index].querySelector('.icon').classList.add(`text-${themeData.domain}-primary`);
    themeCards[index].querySelector('.icon').parentElement.classList.add(`bg-${themeData.domain}-bg`);
    themeCards[index].querySelector('.id-phrase').textContent = themeData.phrase;
    themeCards[index].querySelector('.id-phrase').classList.add(`text-${themeData.domain}-sep`);
    themeCards[index].querySelector('.rank').textContent = `#${i}`;
    themeCards[index].querySelector('.rank').classList.add(`text-${themeData.domain}-sep`)

    const themeSection = themeIntroSections[index];

    const themeCardSeparators = themeSection.querySelectorAll('.cards .sep');
    themeCardSeparators.forEach((sep) => sep.classList.add(`bg-${themeData.domain}-sep`));

    const themeCardTitles = themeSection.querySelectorAll('.cards .title');
    themeCardTitles.forEach((title) => title.classList.add(`text-${themeData.domain}-primary`));

    themeSection.querySelector('.cards .domain-card .text').textContent = data.domains[themeData.domain].name;
    themeSection.querySelector('.cards .domain-card .text').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.cards .english-name-card .text').textContent = helper.formatter(theme);
    themeSection.querySelector('.cards .english-name-card .text').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.cards .rank-card .text').textContent = `#${i}`;
    themeSection.querySelector('.cards .rank-card .text').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-icon').textContent = themeData.icon;
    themeSection.querySelector('.theme-icon').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-title').textContent = themeData.name;
    themeSection.querySelector('.theme-title').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-chart').classList.add(`text-${themeData.domain}-primary`);
    themeSection.querySelector('.theme-chart').setAttribute('stroke-dasharray', `${themeData.dominancePercentage}, 100`);

    themeSection.querySelector('.theme-percentage').textContent = `${themeData.dominancePercentage}%`;
    themeSection.querySelector('.theme-percentage').classList.add(`text-${themeData.domain}-primary`);

    themeSection.querySelector('.theme-description').textContent = themeData.onWeaknessDescription;

    const themeObstaclesContainer = themeSection.querySelector('.theme-obstacles');
    const obstacleTemplate = themeObstaclesContainer.querySelector('.item');
    themeObstaclesContainer.innerHTML = '';

    themeData.onWeaknessObstacles.forEach( function (obstacle) {
      const item = obstacleTemplate.cloneNode(true);
      item.querySelector('.text').textContent = obstacle;
      
      themeObstaclesContainer.appendChild(item);
    });
    
    const themeOpportunitiesContainer = themeSection.querySelector('.theme-opportunities');
    const opportunityTemplate = themeOpportunitiesContainer.querySelector('.item');
    themeOpportunitiesContainer.innerHTML = '';

    themeData.onWeaknessOpportunities.forEach( function (opportunity) {
      const item = opportunityTemplate.cloneNode(true);
      item.querySelector('.text').textContent = opportunity;
      
      themeOpportunitiesContainer.appendChild(item);
    });
  }
  })();

  showTab("summary");
}

function getElementBuilder() {
  const builder = {};

  builder.ThemeIconBox = function(position, theme, color) {
    return `
        <div id="theme-pos-${position}" class="flex flex-col items-center p-4 border rounded-xl" style="border-color:${color}">
          <div class="theme-icon icon-${theme.icon} w-10 h-10 mb-2"></div>
          <div class="theme-name font-medium text-center">${theme.name}</div>
        </div>
      `.trim();
  };
  
  builder.otherThemesBox = function(position, theme, color) {
    return `
      <div id="theme-pos-${position}" class="p-3 border rounded-lg flex flex-col">
          <span class="text-sm font-semibold">${position}</span>
          <span class="font-medium">${theme.name}</span>
      </div>
    `;
  };

  return builder;
}

function getHelper() {
  helper = {};

  helper.findDominanceLevel = function (percentage) {
    return percentage < 30 ? 'کم' :
           percentage < 60 ? 'متوسط' :
           'زیاد';
    
  };

  helper.formatter = function (string) {
    string = string.replace('_', ' ');
    return string
      .split(" ")
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");
  };

  helper.getResourceUrl = function (resourceName) {
    const relativePath = resourceName.replace('.', '/');
    const base = `assets/images/${relativePath}`; 
    const url = new URL(`${base}`, window.location.href).href
    return url;
  };

  helper.getMostDominantTheme = function (degrees) {
    const [mostDominantTheme, _] = Object.entries(degrees)
      .reduce((max, entry) => entry[1] > max[1] ? entry : max);
    return mostDominantTheme;
  };

  helper.getLeastDominantTheme = function (degrees) {
    const [leastDominantTheme, _] = Object.entries(degrees)
      .reduce((min, entry) => entry[1] < min[1] ? entry : min);
    return leastDominantTheme;
  };

  helper.getMostDominantDomain = function (degrees, themes) {
    const domains = {};
    for (const [theme, degree] of Object.entries(degrees)) {
      
      domains[themes[theme]["domain"]] += degree;
    }

    const [dominantDomain, value] = Object.entries(domains)
      .reduce((max, entry) => entry[1] > max[1] ? entry : max);

    return dominantDomain;
  };

  helper.getSortedThemes = function (degrees) {
    const themes = Object.entries(degrees)
      .sort((a, b) => b[1] - a[1])
      .map(([key]) => key);
    return themes;
  }; 

  helper.getSortedDomains = function (degrees, themes) {
    const domains = {};
    for (const [theme, degree] of Object.entries(degrees)) {
      domains[themes[theme]["domain"]] = domains[themes[theme]["domain"]] + degree || degree;
    }
    const result = [];
    for (let [domain, degree] of Object.entries(domains).sort((a, b) => a[1] - b[1])) {
      result.push([domain, degree]);
    } 
    return result;
  };

  return helper;
}

function setTextById(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

// ===== تب‌ها و ریز ابزار =====
function showTab(tabName) {
  document
    .querySelectorAll(".tab-content")
    .forEach((t) => t.classList.add("hidden"));
  document.querySelectorAll(".tab-btn").forEach((b) => {
    b.classList.remove("text-primary-600", "active");
    b.classList.add("text-gray-500", "hover:text-gray-700");
  });
  document.getElementById(`${tabName}-tab`)?.classList.remove("hidden");
  const activeBtn = document.querySelector(`[onclick="showTab('${tabName}')"]`);
  if (activeBtn) {
    activeBtn.classList.remove("text-gray-500", "hover:text-gray-700");
    activeBtn.classList.add("text-primary-600", "active");
  }

  if (tabName === "ai-chat" && !chatInitialized) {
    initializeChatBot();
  }
}

function initializeChatBot() {
  const chatBox = document.getElementById("chat-box");
  const userInput = document.getElementById("user-input");
  const sendButton = document.getElementById("send-button");
  const stopButton = document.getElementById("stop-button");
  const clearButton = document.getElementById("clear-button");

  if (
    !chatBox ||
    !userInput ||
    !sendButton ||
    !stopButton ||
    !clearButton ||
    chatInitialized
  ) {
    console.error(
      "One or more chat elements are missing or chat is already initialized"
    );
    return;
  }
  chatInitialized = true;

  // ===== پیکربندی اکشن و آدرس اندپوینت =====
  const ACTION_NAME = "clifton_chatbot_request";
  // اولویت با اندپوینت اختصاصی چت؛ سپس fallback
  const endpointUrl =
    (typeof cliftonChatbot !== "undefined" && cliftonChatbot.ajaxurl) ||
    (typeof cliftonAjax !== "undefined" && cliftonAjax.ajax_url) ||
    "/personality/clifton/api/api.php";

  console.log("clifton Chat endpoint:", endpointUrl);

  const messageCache = new Map();
  let controller = null;
  let isRequestActive = false;

  // ===== داده‌های ذخیره‌شده clifton =====
  const cliftonStored = (() => {
    try {
      return JSON.parse(localStorage.getItem("cliftonResults")) || {};
    } catch {
      return {};
    }
  })();

  const userMeta = cliftonStored.userInfo || {
    date: "نامشخص",
    birthYear: 1400,
    gender: "نامشخص",
  };
  const selectedTestOption = cliftonStored.selectedTestOption || "free";

  // پاک کردن چت‌باکس
  chatBox.innerHTML = "";

  // ===== پیام خوش‌آمد clifton =====
  function showWelcomeMessage() {
    const welcomeMessage = `
      سلام! 👋 خوش اومدی به بخش تحلیل نتایج شخصیت‌شناسی کلیفتون.
من اینجام تا کمکت کنم تم‌های استعدادی‌ات رو بهتر بشناسی، معنی هر نتیجه رو بفهمی و پیشنهادهای اختصاصی برای مسیر تحصیلی، شغلی و رشد فردی دریافت کنی.

اگه دوست داری، می‌تونی بهم بگی:

تم‌هات چیا هستن؟

کدوم نتیجه برات سوال ایجاد کرده؟

یا اینکه راهنمایی شغلی، تحصیلی یا تحلیلی میخوای؟

هرجور راحتی—شروع کنیم؟ 😊`;
    appendMessage("bot", welcomeMessage);
  }

  // نمایش پیام خوش‌آمد در اولین ورود به تب AI
  if (!localStorage.getItem("cliftonFirstVisit")) {
    showWelcomeMessage();
    localStorage.setItem("cliftonFirstVisit", "true");
  }

  // ===== لود تاریخچه clifton =====
  const savedMessages = (() => {
    try {
      return JSON.parse(localStorage.getItem("cliftonChatHistory")) || [];
    } catch {
      return [];
    }
  })();
  savedMessages.forEach((msg) =>
    appendMessage(msg.role, msg.content, msg.time, false)
  );

  // ===== سوالات پیشنهادی clifton =====
  const suggestedQuestions = [
    `«می‌تونی برام بگی هر کدوم از تم‌هام چه معنی‌ای دارن؟»`,
    `«قوی‌ترین استعداد من چه مزیت‌هایی به من می‌ده؟»`,
    `«بر اساس تم‌های من چه رشته‌هایی مناسب‌تر هستن؟»`,
    `«کدوم تم‌هام مکمل همدیگه هستن؟»`,
    `«چه شغل‌هایی با ترکیب استعدادهای من همخوانی دارن؟»`
  ];
  const suggestedContainer = document.createElement("div");
  suggestedContainer.className = "suggested-questions";
  suggestedQuestions.forEach((q) => {
    const btn = document.createElement("button");
    btn.className = "suggested-question-btn";
    btn.textContent = q;
    btn.addEventListener("click", () => {
      if (!isRequestActive) {
        userInput.value = q;
        sendMessage();
      }
    });
    suggestedContainer.appendChild(btn);
  });
  const inputWrap = document.querySelector(".input-container");
  if (inputWrap)
    inputWrap.insertAdjacentElement("afterend", suggestedContainer);

  // ===== UI کمک‌تابع‌ها =====
  function appendMessage(
    role,
    content,
    time = new Date().toLocaleTimeString("fa-IR"),
    saveToHistory = true
  ) {
    const cleanedContent = cleanText(content);
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${role}-message`;
    const avatarDiv = document.createElement("div");
    avatarDiv.className = "avatar";
    const bubbleDiv = document.createElement("div");
    bubbleDiv.className = "message-bubble";
    bubbleDiv.innerHTML = cleanedContent;
    const timeDiv = document.createElement("div");
    timeDiv.className = "message-time";
    timeDiv.textContent = time;
    messageDiv.appendChild(avatarDiv);
    messageDiv.appendChild(bubbleDiv);
    messageDiv.appendChild(timeDiv);
    chatBox.appendChild(messageDiv);
    chatBox.scrollTop = chatBox.scrollHeight;

    if (saveToHistory) {
      const hist = (() => {
        try {
          return JSON.parse(localStorage.getItem("cliftonChatHistory")) || [];
        } catch {
          return [];
        }
      })();
      hist.push({ role, content, time });
      localStorage.setItem("cliftonChatHistory", JSON.stringify(hist));
    }
  }

  function cleanText(text) {
    if (!text) return '<p class="text-white-600">پاسخی دریافت نشد.</p>';
    let cleaned = text
      .replace(
        /^###+\s*(.*?)\s*$/gm,
        '<h5 class="font-bold text-base text-white-700 mb-2">$1</h5>'
      )
      .replace(
        /^##+\s*(.*?)\s*$/gm,
        '<h4 class="font-bold text-lg text-white-800 mb-3">$1</h4>'
      )
      .replace(
        /^#+\s*(.*?)\s*$/gm,
        '<h4 class="font-bold text-lg text-white-800 mb-3">$1</h4>'
      )
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>")
      .replace(
        /\[([^\]]+)\]\(([^\)]+)\)/g,
        '<a href="$2" class="text-primary-600 hover:underline" target="_blank">$1</a>'
      )
      .replace(
        /^\s*-\s*(.*)$/gm,
        '<li class="flex items-start"><span class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center mt-1 mr-2"><i class="fas fa-check text-primary-600 text-xs"></i></span><span>$1</span></li>'
      )
      .replace(
        /^\s*\*\s*(.*)$/gm,
        '<li class="flex items-start"><span class="w-6 h-6 bg-primary-100 rounded-full flex items-center justify-center mt-1 mr-2"><i class="fas fa-check text-primary-600 text-xs"></i></span><span>$1</span></li>'
      )
      .replace(
        /`([^`]+)`/g,
        '<code class="bg-gray-100 text-white-800 px-1 rounded">$1</code>'
      )
      .replace(
        /```[\s\S]*?```/g,
        (m) =>
          `<pre class="bg-gray-100 p-3 rounded text-white-800"><code>${m
            .replace(/```/g, "")
            .trim()}</code></pre>`
      )
      .replace(/\n{2,}/g, '</p><p class="text-white-600 mt-2">')
      .replace(/\n/g, "<br>")
      .replace(/\s+/g, " ")
      .trim();

    if (cleaned.includes("<li")) {
      cleaned = cleaned.replace(/(<li.*?<\/li>)/g, "$1");
      cleaned = `<ul class="space-y-3">${cleaned}</ul>`;
    }
    if (!cleaned.match(/<(h4|h5|li|pre)\b/)) {
      cleaned = `<p class="text-white-600">${cleaned}</p>`;
    } else {
      cleaned = cleaned.replace(/<p class="text-white-600"><\/p>/g, "");
    }
    return cleaned;
  }

  function resetInputState() {
    isRequestActive = false;
    userInput.disabled = false;
    sendButton.disabled = false;
    stopButton.style.display = "none";
    sendButton.style.display = "inline-block";
    document.querySelectorAll(".suggested-question-btn").forEach((btn) => {
      btn.disabled = false;
      btn.style.opacity = "1";
      btn.style.cursor = "pointer";
    });
  }

  // ===== ارسال پیام =====
  async function sendMessage() {
    if (isRequestActive) return;

    const message = userInput.value.trim();
    if (!message) {
      appendMessage("bot", "لطفاً یک پیام وارد کنید.");
      return;
    }

    appendMessage("user", message);
    userInput.value = "";
    isRequestActive = true;

    userInput.disabled = true;
    sendButton.disabled = true;
    document.querySelectorAll(".suggested-question-btn").forEach((btn) => {
      btn.disabled = true;
      btn.style.opacity = "0.5";
      btn.style.cursor = "not-allowed";
    });

    const loadingMessage = document.createElement("div");
    loadingMessage.className = "message bot-message loading";
    loadingMessage.innerHTML = `
      <div class="avatar"></div>
      <div class="message-bubble">
        <div class="ai-loader">
          <div class="ai-loader-dot"></div>
          <div class="ai-loader-dot"></div>
          <div class="ai-loader-dot"></div>
        </div>
      </div>
      <div class="message-time">${new Date().toLocaleTimeString(
      "fa-IR"
    )}</div>`;
    chatBox.appendChild(loadingMessage);
    chatBox.scrollTop = chatBox.scrollHeight;

    sendButton.style.display = "none";
    stopButton.style.display = "inline-block";

    if (messageCache.has(message)) {
      chatBox.removeChild(loadingMessage);
      appendMessage("bot", messageCache.get(message));
      resetInputState();
      return;
    }

    try {
      controller = new AbortController();

      // آماده‌سازی دیتا برای بک‌اند clifton
      const formData = new FormData();
      formData.append("action", ACTION_NAME);
      if (typeof cliftonAjax !== "undefined" && cliftonAjax.nonce) {
        formData.append("nonce", cliftonAjax.nonce);
      }
      formData.append("message", message);
      formData.append("user_info", JSON.stringify(userMeta));
      formData.append("selected_test_option", selectedTestOption);
      if (cliftonStored.scores) {
        formData.append("scores", JSON.stringify(cliftonStored.scores));
      }

      const response = await fetch(endpointUrl, {
        method: "POST",
        body: formData,
        signal: controller.signal,
      });

      if (!response.ok) {
        throw new Error(`خطای سرور: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      if (!data || data.success === false) {
        throw new Error(
          (data && (data.message || data.error)) || "پاسخی از سرور دریافت نشد."
        );
      }

      const serverMessage =
        data.message || data.reply || data.data || "پاسخ دریافت شد.";
      const botMessage = cleanText(String(serverMessage));

      messageCache.set(message, botMessage);
      chatBox.removeChild(loadingMessage);
      appendMessage("bot", botMessage);
    } catch (error) {
      if (loadingMessage.parentNode) chatBox.removeChild(loadingMessage);
      appendMessage(
        "bot",
        error.message || "خطایی رخ داد. لطفاً دوباره امتحان کنید."
      );
      console.error("clifton Chat AJAX Error:", error);
    } finally {
      resetInputState();
    }
  }

  // ===== رویدادها =====
  sendButton.addEventListener("click", sendMessage);
  userInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && !e.shiftKey && !isRequestActive) {
      e.preventDefault();
      sendMessage();
    }
  });

  stopButton.addEventListener("click", () => {
    if (controller) {
      controller.abort();
      const loading = document.querySelector(".bot-message.loading");
      if (loading && loading.parentNode)
        loading.parentNode.removeChild(loading);
      appendMessage("bot", "درخواست متوقف شد.");
      resetInputState();
    }
  });

  clearButton.addEventListener("click", () => {
    chatBox.innerHTML = "";
    localStorage.removeItem("cliftonChatHistory");
    showWelcomeMessage();
  });
}

// ===== لود ابتدایی =====
window.addEventListener("load", () => {
  if (typeof jQuery === "undefined") {
    alert("خطا در بارگذاری کتابخانه‌ها.");
    return;
  }
  loadQuestions();
});
function shareResults() {
  const url = location.href;
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url);
  } else {
    const ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
  }
}
function shareResults() {
  const url = location.href;
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url)
      .then(() => showToast('لینک کپی شد ✅'))
      .catch(() => fallbackCopy(url));
  } else {
    fallbackCopy(url);
  }
}

function fallbackCopy(text) {
  try {
    const ta = document.createElement('textarea');
    ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
    showToast('لینک کپی شد ✅');
  } catch (e) {
    console.error(e);
    showToast('کپی نشد ❌');
  }
}

// توست ساده بدون وابستگی
let _toastTimer;
function showToast(msg) {
  let t = document.getElementById('copy-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'copy-toast';
    t.className = 'copy-toast';
    t.setAttribute('role', 'status');
    t.setAttribute('aria-live', 'polite');
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(_toastTimer);
  _toastTimer = setTimeout(() => t.classList.remove('show'), 1700);
}

// ریپل روی کلیک دکمه
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('shareBtn');
  if (!btn) return;
  btn.addEventListener('click', (e) => {
    const rect = btn.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 500);
  }, { passive: true });
});

// ===== صادرات لازم برای HTML =====
window.selectOption = selectOption;
window.startTest = startTest;
window.selectAnswer = selectAnswer;
window.nextQuestion = nextQuestion;
window.prevQuestion = prevQuestion;
window.showResults = showResults;
window.displayResults = displayResults;
window.showTab = showTab;
