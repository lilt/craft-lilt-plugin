const siteLanguages = {
  en: 1, uk: 2, de: 3, es: 4,
};

const originalContent = [
  {"functionName":"val","id": "#title",'value': 'The Future of Augmented Reality'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Personalized ads everywhere you look</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Your iPhone Is No Longer a Way To Hide'},
  {"functionName":"val","id": "#fields-subheading",'value': 'But is now a way to connect with the world'},
  {"functionName":"text","id": "#fields-articleBody-blocks-25-fields-text",'value': "<p>When you're watching the world through a screen, you forget what's real and what's not. This creates some exciting opportunities for advertisers.<br /><br />Imagine this scenario: you're walking to a coffee shop and hear one of your favorite songs from your college days. You turn to see a car coming down the street, and the driver looks like a younger version of yourself. <br /><br />He gives you the slightest nod as he passes, and it brings back warm memories of your carefree youth.<br /><br />Later, when you order your coffee, you see an ad for the car projected on to your cup. If you want to do a test drive, just click 'yes' and the car will come pick you up.<br /></p>"},
  {"functionName":"val","id": "#fields-articleBody-blocks-30-fields-pullQuote",'value': 'You turn to see a car coming down the street, and the driver looks like a younger version of yourself.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-32-fields-heading",'value': 'A People-to-People Business'},
  {"functionName":"val","id": "#fields-articleBody-blocks-33-fields-text",'value': '<p>Each person wants a slightly different version of reality. Now they can get it.<br /><br /><br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-quote",'value': 'Augmented reality has long sounded like a wild futuristic concept, but the technology has actually been around for years.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-attribution",'value': 'Charlie Roths, Developers.Google'},
  {"functionName":"val","id": "#fields-articleBody-blocks-35-fields-heading",'value': 'What is Happy Lager Doing About It?'},
  {"functionName":"val","id": "#fields-articleBody-blocks-36-fields-text",'value': '<p>When you drink our beer, we use AI to evaluate your emotional state, and use a proprietary algorithm to generate an artificial environment that provides the exact olfactory, visual, and auditory stimulation you want.<br /><br />Forget about the real world as we blow the smell of your mother\'s cinnamon rolls past your face. <br /><br />Sink into your chair as Dean Martin sings relaxing jazz standards.<br /><br />Play Candy Smash in stunning 8k resolution, with only an occasional ad to extend your viewing experience.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-38-fields-heading",'value': 'This is Only the Beginning'},
  {"functionName":"val","id": "#fields-articleBody-blocks-39-fields-text",'value': '<p>The real world has practical limits on advertisers. The augmented world is only limited by your design budget and production values.</p>'},
];

const ukrainianContent = [
  {"functionName":"val","id": "#title",'value': 'Майбутнє доповненої реальності'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Персоналізовані оголошення скрізь, де ви дивитеся</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Ваш iPhone більше не спосіб приховати'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Але зараз це спосіб зв&#39;язатися зі світом'},
  {"functionName":"text","id": "#fields-articleBody-blocks-25-fields-text",'value': '<p>Коли ви дивитеся світ через екран, ви забуваєте, що реально, а що ні. Це створює деякі цікаві можливості для <br /><br />advertisers.Imagine цей сценарій: ви ходите в кав&#39;ярню і чуєте одну з ваших улюблених пісень з ваших днів коледжу. Ви повертаєтеся, щоб побачити автомобіль, що йде по вулиці, і водій виглядає як молодша версія себе.<br /><br /> Він дає вам найменший the як він проходить, і це повертає теплі спогади про вашу безтурботну youth.Later, коли ви замовляєте каву, ви бачите оголошення для <br /><br />автомобіля, який проектується на вашу чашку. Якщо ви хочете зробити a просто натисніть &quot;так&quot;, і автомобіль підбере вас.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-30-fields-pullQuote",'value': 'Ви повертаєтеся, щоб побачити автомобіль, що йде по вулиці, і водій виглядає як молодша версія себе.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-32-fields-heading",'value': 'Бізнес від людей до людей'},
  {"functionName":"val","id": "#fields-articleBody-blocks-33-fields-text",'value': '<p>Кожна людина хоче трохи іншої версії реальності. Тепер вони можуть отримати його.<br /><br /><br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-quote",'value': 'Розширена реальність вже давно звучала як дика футуристична концепція, але технологія насправді існує вже багато років.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-attribution",'value': 'Charlie Roths, Developers.Google'},
  {"functionName":"val","id": "#fields-articleBody-blocks-35-fields-heading",'value': 'Що Happy Lager робить про це?'},
  {"functionName":"val","id": "#fields-articleBody-blocks-36-fields-text",'value': '<p>Коли ви п&#39;єте наше пиво, ми використовуємо AI для оцінки вашого емоційного стану і використовуємо власний алгоритм для створення штучного середовища, який забезпечує точну нюхову, візуальну і слухову стимуляцію ви хочете. <br /><br />Забудьте про реальний світ, як ми blow запах кориці вашої матері згортає повз вашого обличчя.<br /><br /> Sink в ваш стілець, як Дін Мартін співає розслабляючий джазовий <br /><br />standards.Play Цукерки Smash в приголомшливому 8k дозволі, з лише випадковим оголошенням, щоб продовжити ваш досвід перегляду.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-38-fields-heading",'value': 'Це тільки початок'},
  {"functionName":"val","id": "#fields-articleBody-blocks-39-fields-text",'value': '<p>Реальний світ має практичні обмеження на рекламодавців. Розширений світ обмежений вашим бюджетом дизайну і виробничими цінностями.</p>'},
];

const germanContent = [
  {"functionName":"val","id": "#title",'value': 'Die Zukunft der Augmented Reality'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Personalisierte Anzeigen überall</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Ihr iPhone ist nicht mehr eine Möglichkeit, sich zu verstecken'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Aber ist jetzt eine Möglichkeit, sich mit der Welt zu verbinden'},
  {"functionName":"val","id": "#fields-articleBody-blocks-25-fields-text",'value': '<p>Wenn man die Welt durch einen Bildschirm betrachtet, vergisst man, was real ist und was nicht. Dies schafft einige aufregende Möglichkeiten für <br /><br />advertisers.Imagine Sie sich dieses Szenario vor: Sie gehen zu einem Café und hören eines Ihrer Lieblingslieder aus Ihrem college Sie drehen sich um und sehen ein Auto die Straße hinunterkommen, und der Fahrer sieht aus wie eine jüngere Version von sich selbst.<br /><br /> Er nickt Ihnen das geringste Nicken, wenn er vorbeikommt, und es bringt warme Erinnerungen an Ihre sorglose Jugend zurück. <br /><br />Später, wenn Sie Ihren Kaffee bestellen, sehen Sie eine Anzeige für das Auto auf Ihre Tasse projiziert. Wenn Sie eine Probefahrt machen möchten, klicken Sie einfach auf &quot;Ja&quot; und das Auto holt Sie ab.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-30-fields-pullQuote",'value': 'Sie drehen sich um und sehen ein Auto die Straße hinunterkommen, und der Fahrer sieht aus wie eine jüngere Version von sich selbst.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-32-fields-heading",'value': 'Ein People-to-People'},
  {"functionName":"val","id": "#fields-articleBody-blocks-33-fields-text",'value': '<p>Jeder Mensch will eine etwas andere Version der Realität. Jetzt können sie es bekommen.<br /><br /><br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-quote",'value': 'Augmented Reality klingt schon lange nach einem wilden futuristischen Konzept, aber die Technologie gibt es tatsächlich schon seit Jahren.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-attribution",'value': 'Das ist nur der Anfang: Charlie Roths, Developers.Google'},
  {"functionName":"val","id": "#fields-articleBody-blocks-35-fields-heading",'value': 'Was macht Happy Lager dagegen?'},
  {"functionName":"val","id": "#fields-articleBody-blocks-36-fields-text",'value': '<p>Wenn du unser Bier trinkst, nutzen wir KI, um deinen emotionalen Zustand zu bewerten, und verwenden einen proprietären Algorithmus, um eine künstliche Umgebung zu erzeugen, die genau die olfaktorische, visuelle und auditive Stimulation bietet, die du möchtest. <br /><br />Vergiss die reale Welt, während wir den Geruch der Zimtschnecken deiner Mutter an deinem Gesicht vorbeiblasen.<br /><br /> Sink in deinen Stuhl, während Dean Martin entspannende Jazzstandards singt. <br /><br />Spiele Candy Smash in atemberaubender 8k-Auflösung und nur gelegentlich eine Anzeige, um dein Seherlebnis zu erweitern.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-38-fields-heading",'value': 'Das ist nur der Anfang'},
  {"functionName":"val","id": "#fields-articleBody-blocks-39-fields-text",'value': '<p>Die reale Welt hat praktische Grenzen für Werbetreibende. Die augmentierte Welt ist nur durch Ihr design und Produktionswerte begrenzt.</p>'},
];

const spanishContent = [
  {"functionName":"val","id": "#title",'value': 'El futuro de la realidad aumentada'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Anuncios personalizados en todas partes que mires</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Tu iPhone ya no es una forma de ocultarte'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Pero ahora es una manera de conectarse con el mundo'},
  {"functionName":"text","id": "#fields-articleBody-blocks-25-fields-text",'value': '<p>Cuando estás viendo el mundo a través de una pantalla, olvidas lo que es real y lo que no. Esto crea algunas oportunidades emocionantes para <br /><br />advertisers.Imagine este escenario: estás caminando a una cafetería y escucha una de tus canciones favoritas de tus días de la universidad. Ves para ver un coche que viene por la calle, y el conductor parece una versión más joven de ti mismo.<br /><br /> Él te da el más ligero guiño a medida que él pasa, y trae de vuelta recuerdos cálidos de tu joven <br /><br />passes, cuando usted ordena su café, usted ve un anuncio para el coche proyectado en su taza. Si quieres hacer una unidad de prueba, solo haz clic en &#39;sí&#39; y el coche vendrá a recogerte.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-30-fields-pullQuote",'value': 'Ves para ver un coche que viene por la calle, y el conductor parece una versión más joven de ti mismo.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-32-fields-heading",'value': 'Un negocio de gente a gente'},
  {"functionName":"val","id": "#fields-articleBody-blocks-33-fields-text",'value': '<p>Cada persona quiere una versión ligeramente diferente de la realidad. Ahora pueden conseguirlo.<br /><br /><br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-quote",'value': 'La realidad aumentada ha sonado durante mucho tiempo como un concepto futurista salvaje, pero la tecnología ha estado en realidad durante años.'},
  {"functionName":"val","id": "#fields-articleBody-blocks-34-fields-attribution",'value': 'Este es solo el Principio: Charlie Roths, Developers.Google'},
  {"functionName":"val","id": "#fields-articleBody-blocks-35-fields-heading",'value': '¿Qué es Happy Lager haciendo al respecto?'},
  {"functionName":"val","id": "#fields-articleBody-blocks-36-fields-text",'value': '<p>Cuando usted bebe nuestra cerveza, usamos la IA para evaluar su estado emocional, y usar un algoritmo patentado para generar un ambiente artificial que proporciona la state, la visualización y la estimulación auditiva que usted desea. <br /><br />Olvídate del mundo real a medida que soplamos el olor de la canela de tu madre pasa tu rostro.<br /><br /> Sumérgete en tu silla como Dean Martin canta jazz relajante <br /><br />standards.Play Candy Smash en impresionante resolución 8k, con solo un anuncio ocasional para extender tu experiencia de visualización.<br /></p>'},
  {"functionName":"val","id": "#fields-articleBody-blocks-38-fields-heading",'value': 'Este es solo el Principio'},
  {"functionName":"val","id": "#fields-articleBody-blocks-39-fields-text",'value': '<p>El mundo real tiene límites prácticos para los anunciantes. El mundo aumentado solo está limitado por tu presupuesto de diseño y valores de producción.</p>'},
];

const translations = {
  es: spanishContent,
  de: germanContent,
  uk: germanContent
}

function translateContent(content, language)
{
  if(!translations[language]) {
    throw new Error(`Can't find translation for language: ${language}`);
  }

  for (let i = 0; i < translations[language].length; i++) {
    if(originalContent[i].id !== translations[language][i].id){
      throw new Error(`Content doesnt match ${originalContent[i].id} !== ${translations[language][i].id}`);
    }

    content = replaceValueInArray(content, originalContent[i].value, translations[language][i].value);
  }

  return content
}

function replaceValueInArray(arr, value, replace) {
  for (let key in arr) {
    if (typeof arr[key] === 'object' || Array.isArray(arr[key])) {
      arr[key] = replaceValueInArray(arr[key], value, replace);
    } else if (arr[key] === value) {
      arr[key] = replace;
    }
  }

  return arr
}

export { siteLanguages, originalContent, germanContent, ukrainianContent, spanishContent, translateContent }
