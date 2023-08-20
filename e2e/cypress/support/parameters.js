const siteLanguages = {
  en: 1, uk: 2, de: 3, es: 4,
};

/**
 * To get all needed selectors you can use:
 * var result = {};
 *
 * $('.ni_blocks').each(function(index) {
 *     $(this).find('textarea, input').each(function() {
 *         var id = $(this).attr('id');
 *         var tagName = $(this).prop('tagName').toLowerCase();
 *         if (id) {
 *             result[id] = '.ni_blocks:eq(' + index + ') ' + tagName;
 *         }
 *     });
 * });
 *
 * To test only content:
 *
 * // it('with copy slug disabled & enable after publish disabled', () => {
 *       //   const {jobTitle, slug} = generateJobData();
 *       //
 *       //   cy.assertEntryContent(
 *       //     ["en"],
 *       //     'copy_source_text',
 *       //       24
 *       //   )
 *       // });
 *
 * console.log(JSON.stringify(result));
 * */
const originalContent = [
  {"functionName":"val","id": "#title",'value': 'The Future of Augmented Reality'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Personalized ads everywhere you look</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Your iPhone Is No Longer a Way To Hide'},
  {"functionName":"val","id": "#fields-subheading",'value': 'But is now a way to connect with the world'},
  {"functionName":"text","id": ".matrixblock:eq(0) textarea",'value': "<p>When you're watching the world through a screen, you forget what's real and what's not. This creates some exciting opportunities for advertisers.<br /><br />Imagine this scenario: you're walking to a coffee shop and hear one of your favorite songs from your college days. You turn to see a car coming down the street, and the driver looks like a younger version of yourself. <br /><br />He gives you the slightest nod as he passes, and it brings back warm memories of your carefree youth.<br /><br />Later, when you order your coffee, you see an ad for the car projected on to your cup. If you want to do a test drive, just click 'yes' and the car will come pick you up.<br /></p>"},
  {"functionName":"val","id": ".matrixblock:eq(1) input[type=\"text\"]",'value': 'You turn to see a car coming down the street, and the driver looks like a younger version of yourself.'},
  {"functionName":"val","id": ".matrixblock:eq(3) input[type=\"text\"]",'value': 'A People-to-People Business'},
  {"functionName":"val","id": ".matrixblock:eq(5) textarea",'value': '<p>Each person wants a slightly different version of reality. Now they can get it.<br /><br /><br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(0)",'value': 'Augmented reality has long sounded like a wild futuristic concept, but the technology has actually been around for years.'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(1)",'value': 'Charlie Roths, Developers.Google'},
  {"functionName":"val","id": ".matrixblock:eq(7) input[type=\"text\"]",'value': 'What is Happy Lager Doing About It?'},
  {"functionName":"val","id": ".matrixblock:eq(8) textarea",'value': '<p>When you drink our beer, we use AI to evaluate your emotional state, and use a proprietary algorithm to generate an artificial environment that provides the exact olfactory, visual, and auditory stimulation you want.<br /><br />Forget about the real world as we blow the smell of your mother\'s cinnamon rolls past your face. <br /><br />Sink into your chair as Dean Martin sings relaxing jazz standards.<br /><br />Play Candy Smash in stunning 8k resolution, with only an occasional ad to extend your viewing experience.<br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(10) input[type=\"text\"]",'value': 'This is Only the Beginning'},
  {"functionName":"val","id": ".matrixblock:eq(11) textarea",'value': '<p>The real world has practical limits on advertisers. The augmented world is only limited by your design budget and production values.</p>'},

  // NEO Fields
  {"functionName":"val","type": "nested", "element": "input", "id": ".ni_blocks:eq(0) input",'value': 'The sun slowly descended behind the mountains, casting a warm golden glow across the tranquil lake and its surrounding landscape'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".ni_blocks:eq(0) textarea",'value': '<p>The old oak tree, its gnarled branches reaching out like ancient fingers, stood as a silent witness to the passage of time. Each year, as the seasons changed, it shed its leaves, only to be reborn in a vibrant explosion of green come spring. Generations of birds nested in its canopy, their songs filling the air with melodies of life and renewal. Underneath its protective shade, children played, their laughter mingling with the rustling of leaves. As the sun set, casting long shadows across the meadow, the tree stood tall, a sentinel of nature\'s resilience and enduring beauty.</p>'},
  {"functionName":"val","type": "nested", "element": "input", "id": ".matrixblock:eq(12) input[type=\"text\"]",'value': 'The diligent student carefully read the challenging textbook, absorbing knowledge to excel in their upcoming exam.'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".matrixblock:eq(13) textarea",'value': '<p>The bustling metropolis hummed with life, its streets teeming with people from all walks of life. Skyscrapers pierced the sky, their glass facades reflecting the vibrant city lights, while taxis weaved through traffic, their horns blaring impatiently. Street performers entertained passersby, their talents mesmerizing audiences with music, dance, and magic. Cafes spilled over with conversation, the aroma of freshly brewed coffee mingling with the tantalizing scent of international cuisines. Amidst the chaos, the city exuded a magnetic energy, a melting pot of cultures and dreams, where stories unfolded and dreams were pursued against all odds.<br /></p>'},

  // SuperTable Fields
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-firstfield",'value': 'The vibrant flowers bloomed gracefully, filling the air with a delightful fragrance that awakened the senses and brought joy to all.'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-secondfield",'value': '<p>The vast expanse of the desert stretched out before them, a barren landscape of shifting sands and endless horizons. With each step, the grains of sand whispered beneath their feet, carried by the wind in an ever-changing dance. The scorching sun beat down upon them, its rays searing their skin, while mirages shimmered in the distance, teasing their senses with illusions of water and oasis. Yet, amidst the harshness, a quiet beauty emerged the delicate patterns etched by the wind, the resilience of desert flora, and the breathtaking spectacle of stars illuminating the night sky.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-thirdfield",'value': '<p>The grand concert hall stood with regal elegance, its ornate architecture a testament to craftsmanship and artistic expression. As the doors opened, anticipation filled the air, mingling with the murmurs of the audience. The orchestra tuned their instruments, the strings resonating with harmonious vibrations, and the conductor raised the baton. A symphony unfolded, melodies intertwining and crescendos swelling, taking the listeners on an emotional journey. From the haunting notes of a violin solo to the thunderous crashes of percussion, the music transcended barriers, transporting souls to realms where words were rendered unnecessary.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-firstfield",'value': 'The sun dipped below the horizon, painting the sky in hues of orange and pink, creating a breathtaking spectacle'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-secondfield",'value': '<p>A serene lake nestled between majestic mountains, its surface glistening like a mirror reflecting the sky above. Silence embraced the surroundings, broken only by the gentle lapping of water against the shore and the occasional call of a distant bird. Trees stood sentinel along the banks, their vibrant foliage mirroring the colors of autumn. In this tranquil oasis, time seemed to slow, allowing weary souls to find solace and reconnect with the rhythms of nature. As the sun dipped below the peaks, painting the sky in hues of orange and purple, the lake embraced the night, its stillness a sanctuary for dreams.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-thirdfield",'value': '<p>The ancient ruins whispered tales of civilizations long gone, their crumbling facades etched with the echoes of history. Stone pillars stood like sentinels, remnants of a grandeur that time had eroded. In the midst of the ruins, one could almost envision the bustling marketplaces, the chants of philosophers, and the fervor of religious ceremonies. As the wind swept through the remnants, carrying the dust of ages, a sense of awe and humility washed over visitors, reminding them of the impermanence of human endeavors and the enduring legacy left behind for generations to ponder.</p>'},
];

const ukrainianContent = [
  {"functionName":"val","id": "#title",'value': 'Майбутнє доповненої реальності'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Персоналізовані оголошення скрізь, де ви дивитеся</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Ваш iPhone більше не спосіб приховати'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Але зараз це спосіб зв&#39;язатися зі світом'},
  {"functionName":"text","id": ".matrixblock:eq(0) textarea",'value': '<p>Коли ви дивитеся світ через екран, ви забуваєте, що реально, а що ні. Це створює деякі цікаві можливості для <br /><br />advertisers.Imagine цей сценарій: ви ходите в кав\'ярню і чуєте одну з ваших улюблених пісень з ваших днів коледжу. Ви повертаєтеся, щоб побачити автомобіль, що йде по вулиці, і водій виглядає як молодша версія себе.<br /><br /> Він дає вам найменший the як він проходить, і це повертає теплі спогади про вашу безтурботну youth.Later, коли ви замовляєте каву, ви бачите оголошення для <br /><br />автомобіля, який проектується на вашу чашку. Якщо ви хочете зробити a просто натисніть "так", і автомобіль підбере вас.<br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(1) input[type=\"text\"]",'value': 'Ви повертаєтеся, щоб побачити автомобіль, що йде по вулиці, і водій виглядає як молодша версія себе.'},
  {"functionName":"val","id": ".matrixblock:eq(3) input[type=\"text\"]",'value': 'Бізнес від людей до людей'},
  {"functionName":"val","id": ".matrixblock:eq(5) textarea",'value': '<p>Кожна людина хоче трохи іншої версії реальності. Тепер вони можуть отримати його.<br /><br /><br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(0)",'value': 'Розширена реальність вже давно звучала як дика футуристична концепція, але технологія насправді існує вже багато років.'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(1)",'value': 'Charlie Roths, Developers.Google'},
  {"functionName":"val","id": ".matrixblock:eq(7) input[type=\"text\"]",'value': 'Що Happy Lager робить про це?'},
  {"functionName":"val","id": ".matrixblock:eq(8) textarea",'value': '<p>Коли ви п\'єте наше пиво, ми використовуємо AI для оцінки вашого емоційного стану і використовуємо власний алгоритм для створення штучного середовища, який забезпечує точну нюхову, візуальну і слухову стимуляцію ви хочете. <br><br>Забудьте про реальний світ, як ми blow запах кориці вашої матері згортає повз вашого обличчя.<br><br> Sink в ваш стілець, як Дін Мартін співає розслабляючий джазовий <br><br>standards.Play Цукерки Smash в приголомшливому 8k дозволі, з лише випадковим оголошенням, щоб продовжити ваш досвід перегляду.<br></p>'},
  {"functionName":"val","id": ".matrixblock:eq(10) input[type=\"text\"]",'value': 'Це тільки початок'},
  {"functionName":"val","id": ".matrixblock:eq(11) textarea",'value': '<p>Реальний світ має практичні обмеження на рекламодавців. Розширений світ обмежений вашим бюджетом дизайну і виробничими цінностями.</p>'},

  // NEO
  {"functionName":"val","type": "nested", "element": "input", "id": ".ni_blocks:eq(0) input",'value': 'Сонце повільно спускалося за гори, відливаючи тепле золоте сяйво на тихе озеро та навколишній ландшафт'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".ni_blocks:eq(0) textarea",'value': '<p>Мовчазним свідком плину часу стояв старий дуб, його вузлуваті гілки простягалися, як стародавні пальці. Щороку, коли змінювалися пори року, воно скидало листя, щоб навесні знову відродитися у яскравому вибуху зелені. У його пологах гніздилися покоління птахів, їхні пісні наповнювали повітря мелодіями життя й оновлення. Під його захисною тінню гралися діти, їхній сміх змішувався з шелестом листя. Коли сонце заходило, відкидаючи довгі тіні на галявину, дерево стояло високим, вартовим стійкості природи та довговічної краси.</p>'},
  {"functionName":"val","type": "nested", "element": "input", "id": ".matrixblock:eq(12) input[type=\"text\"]",'value': 'Старанний студент уважно читав складний підручник, вбираючи знання, щоб відзначитися на майбутньому іспиті.'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".matrixblock:eq(13) textarea",'value': '<p>Гамінливий мегаполіс вирував життям, його вулиці кишили людьми з усіх верств суспільства. Хмарочоси пронизували небо, їхні скляні фасади відбивали яскраві вогні міста, у той час як таксі проносилися крізь рух, нетерпляче гукаючи гудками. Вуличні артисти розважали перехожих, їхні таланти зачаровували публіку музикою, танцями та магією. Кафе переповнені розмовами, аромат свіжозвареної кави змішується з спокусливим ароматом міжнародної кухні. Серед хаосу місто випромінювало магнетичну енергію, плавильний котел культур і мрій, де розгорталися історії та втілювалися в життя, незважаючи ні на що.<br /></p>'},

  // SuperTable Fields
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-firstfield",'value': 'Яскраві квіти витончено розцвіли, наповнюючи повітря чудовим ароматом, який пробуджував почуття та дарував усім радість.'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-secondfield",'value': '<p>Перед ними розкинувся величезний простір пустелі, безплідний ландшафт плинних пісків і нескінченних горизонтів. З кожним кроком піщинки шепотіли під їхніми ногами, несучись вітром у постійному танці. Палюче сонце палало на них, його промені обпікали шкіру, а міражі мерехтіли вдалині, дратуючи їхні почуття ілюзіями води та оазису. Проте серед суворості з’явилася тиха краса тонкі візерунки, викарбувані вітром, стійкість пустельної флори та захоплююче видовище зірок, що освітлюють нічне небо.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-thirdfield",'value': '<p>Великий концертний зал вирізнявся королівською елегантністю, його вишукана архітектура свідчила про майстерність і мистецьке вираження. Коли двері відчинилися, передчуття наповнило повітря, змішуючись із шепотом залу. Оркестр налаштував свої інструменти, струни резонували гармонійними коливаннями, а диригент підняв диригентську паличку. Розгорталася симфонія, мелодії перепліталися і звучали крещендо, переносячи слухачів у емоційну подорож. Від приголомшливих нот скрипкового соло до громового гуркоту перкусії, музика долала бар’єри, переносячи душі в сфери, де слова були непотрібними.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-firstfield",'value': 'Сонце занурилося за горизонт, розфарбовуючи небо в помаранчеві та рожеві відтінки, створюючи захоплююче видовище'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-secondfield",'value': '<p>Тихе озеро, розташоване між величними горами, його поверхня блищить, як дзеркало, що відображає небо. Навколо панувала тиша, яку порушували лише ніжне плескіт води об берег і час від часу поклик далекого птаха. Вздовж берега стояли дерева, яскраве листя яких відбивало кольори осені. У цьому спокійному оазисі час ніби сповільнився, дозволяючи втомленим душам знайти розраду та відновити зв’язок із ритмами природи. Коли сонце занурилося за вершини, розфарбовуючи небо в помаранчеві та фіолетові відтінки, озеро обійняло ніч, а його тиша стала притулком для мрій.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-thirdfield",'value': '<p>Стародавні руїни шепотіли розповідями про цивілізації, що давно минули, а їхні фасади, що руйнуються, вкарбовані відлунням історії. Кам’яні стовпи стояли, як вартові, залишки величі, яку знищив час. Посеред руїн можна було майже уявити гамірні ринкові площі, співи філософів і запал релігійних церемоній. Коли вітер проносився крізь руїни, несучи порох віків, почуття благоговіння та смирення охопило відвідувачів, нагадуючи їм про непостійність людських зусиль і довговічну спадщину, залишену для роздумів поколінням.</p>'},

];

const germanContent = [
  {"functionName":"val","id": "#title",'value': 'Die Zukunft der Augmented Reality'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Personalisierte Anzeigen überall</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Ihr iPhone ist nicht mehr eine Möglichkeit, sich zu verstecken'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Aber ist jetzt eine Möglichkeit, sich mit der Welt zu verbinden'},
  {"functionName":"val","id": ".matrixblock:eq(0) textarea",'value': '<p>Wenn man die Welt durch einen Bildschirm betrachtet, vergisst man, was real ist und was nicht. Dies schafft einige aufregende Möglichkeiten für <br><br>advertisers.Imagine Sie sich dieses Szenario vor: Sie gehen zu einem Café und hören eines Ihrer Lieblingslieder aus Ihrem college Sie drehen sich um und sehen ein Auto die Straße hinunterkommen, und der Fahrer sieht aus wie eine jüngere Version von sich selbst.<br><br> Er nickt Ihnen das geringste Nicken, wenn er vorbeikommt, und es bringt warme Erinnerungen an Ihre sorglose Jugend zurück. <br><br>Später, wenn Sie Ihren Kaffee bestellen, sehen Sie eine Anzeige für das Auto auf Ihre Tasse projiziert. Wenn Sie eine Probefahrt machen möchten, klicken Sie einfach auf "Ja" und das Auto holt Sie ab.<br></p>'},
  {"functionName":"val","id": ".matrixblock:eq(1) input[type=\"text\"]",'value': 'Sie drehen sich um und sehen ein Auto die Straße hinunterkommen, und der Fahrer sieht aus wie eine jüngere Version von sich selbst.'},
  {"functionName":"val","id": ".matrixblock:eq(3) input[type=\"text\"]",'value': 'Ein People-to-People'},
  {"functionName":"val","id": ".matrixblock:eq(5) textarea",'value': '<p>Jeder Mensch will eine etwas andere Version der Realität. Jetzt können sie es bekommen.<br><br><br></p>'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(0)",'value': 'Augmented Reality klingt schon lange nach einem wilden futuristischen Konzept, aber die Technologie gibt es tatsächlich schon seit Jahren.'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(1)",'value': 'Das ist nur der Anfang: Charlie Roths, Developers.Google'},
  {"functionName":"val","id": ".matrixblock:eq(7) input[type=\"text\"]",'value': 'Was macht Happy Lager dagegen?'},
  {"functionName":"val","id": ".matrixblock:eq(8) textarea",'value': '<p>Wenn du unser Bier trinkst, nutzen wir KI, um deinen emotionalen Zustand zu bewerten, und verwenden einen proprietären Algorithmus, um eine künstliche Umgebung zu erzeugen, die genau die olfaktorische, visuelle und auditive Stimulation bietet, die du möchtest. <br><br>Vergiss die reale Welt, während wir den Geruch der Zimtschnecken deiner Mutter an deinem Gesicht vorbeiblasen.<br><br> Sink in deinen Stuhl, während Dean Martin entspannende Jazzstandards singt. <br><br>Spiele Candy Smash in atemberaubender 8k-Auflösung und nur gelegentlich eine Anzeige, um dein Seherlebnis zu erweitern.<br></p>'},
  {"functionName":"val","id": ".matrixblock:eq(10) input[type=\"text\"]",'value': 'Das ist nur der Anfang'},
  {"functionName":"val","id": ".matrixblock:eq(11) textarea",'value': '<p>Die reale Welt hat praktische Grenzen für Werbetreibende. Die augmentierte Welt ist nur durch Ihr design und Produktionswerte begrenzt.</p>'},

  // NEO
  {"functionName":"val","type": "nested", "element": "input", "id": ".ni_blocks:eq(0) input",'value': 'Die Sonne versank langsam hinter den Bergen und warf einen warmen goldenen Schein über den ruhigen See und die umliegende Landschaft'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".ni_blocks:eq(0) textarea",'value': 'Die alte Eiche, deren knorrige Äste sich wie alte Finger ausstreckten, war ein stiller Zeuge des Laufs der Zeit. Jedes Jahr, wenn sich die Jahreszeiten ändern, wirft es seine Blätter ab, um im Frühling in einer leuchtenden Explosion von Grün wiedergeboren zu werden. Generationen von Vögeln nisteten in seinem Blätterdach und ihre Lieder erfüllten die Luft mit Melodien des Lebens und der Erneuerung. Unter seinem schützenden Schatten spielten Kinder, ihr Lachen vermischte sich mit dem Rascheln der Blätter. Als die Sonne unterging und lange Schatten über die Wiese warf, ragte der Baum empor, ein Wächter der Widerstandsfähigkeit und dauerhaften Schönheit der Natur.'},
  {"functionName":"val","type": "nested", "element": "input", "id": ".matrixblock:eq(12) input[type=\"text\"]",'value': 'Der fleißige Schüler las das anspruchsvolle Lehrbuch sorgfältig durch und eignete sich das Wissen an, um in der bevorstehenden Prüfung hervorragende Leistungen zu erbringen.'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".matrixblock:eq(13) textarea",'value': 'In der geschäftigen Metropole brummte das Leben, in den Straßen wimmelt es von Menschen aus allen Gesellschaftsschichten. Wolkenkratzer ragten in den Himmel, ihre Glasfassaden spiegelten die pulsierenden Lichter der Stadt wider, während Taxis mit ungeduldig dröhnenden Hupen durch den Verkehr schlängelten. Straßenkünstler unterhielten die Passanten und faszinierten das Publikum mit Musik, Tanz und Magie. In den Cafes herrschte reges Gespräch, der Duft von frisch gebrühtem Kaffee vermischte sich mit dem verlockenden Duft internationaler Küche. Inmitten des Chaos strahlte die Stadt eine magnetische Energie aus, ein Schmelztiegel der Kulturen und Träume, in dem sich Geschichten entfalteten und Träume allen Widrigkeiten zum Trotz verfolgt wurden.'},

  // SuperTable Fields
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-firstfield",'value': 'Die leuchtenden Blumen erblühten anmutig und erfüllten die Luft mit einem herrlichen Duft, der die Sinne weckte und allen Freude bereitete.'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-secondfield",'value': '<p>Die weite Fläche der Wüste erstreckte sich vor ihnen, eine karge Landschaft aus wechselndem Sand und endlosen Horizonten. Bei jedem Schritt flüsterten die Sandkörner unter ihren Füßen, getragen vom Wind in einem sich ständig verändernden Tanz. Die sengende Sonne brannte auf sie herab, ihre Strahlen verbrannten ihre Haut, während in der Ferne Luftspiegelungen schimmerten und ihre Sinne mit Illusionen von Wasser und Oasen reizten. Doch inmitten der Härte tauchte eine stille Schönheit auf die zarten, vom Wind gezeichneten Muster, die Widerstandsfähigkeit der Wüstenflora und das atemberaubende Schauspiel der Sterne, die den Nachthimmel erhellen.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-thirdfield",'value': '<p>Der große Konzertsaal strahlte königliche Eleganz aus, seine kunstvolle Architektur zeugte von Handwerkskunst und künstlerischem Ausdruck. Als sich die Türen öffneten, lag Vorfreude in der Luft und vermischte sich mit dem Gemurmel des Publikums. Das Orchester stimmte seine Instrumente, die Saiten erklangen in harmonischen Schwingungen und der Dirigent hob den Taktstock. Es entfaltete sich eine Symphonie, in der Melodien ineinander verschlungen und Crescendos anschwellen, die den Zuhörer auf eine emotionale Reise mitnahm. Von den eindringlichen Tönen eines Geigensolos bis hin zu den donnernden Schlägen der Percussion die Musik überwand Grenzen und entführte Seelen in Bereiche, in denen Worte überflüssig waren.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-firstfield",'value': 'Die Sonne versank hinter dem Horizont und tauchte den Himmel in Orange- und Rosatöne, was ein atemberaubendes Schauspiel bot'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-secondfield",'value': '<p>Ein ruhiger See, eingebettet zwischen majestätischen Bergen, dessen Oberfläche glitzert wie ein Spiegel, der den Himmel darüber reflektiert. Stille lag in der Umgebung und wurde nur durch das sanfte Plätschern des Wassers an der Küste und den gelegentlichen Ruf eines entfernten Vogels unterbrochen. An den Ufern standen Bäume, deren leuchtendes Laub die Farben des Herbstes widerspiegelte. In dieser Oase der Ruhe schien die Zeit zu verlangsamen, sodass müde Seelen Trost finden und sich wieder mit den Rhythmen der Natur verbinden konnten. Während die Sonne hinter den Gipfeln versank und den Himmel in Orange- und Lilatönen tauchte, umarmte der See die Nacht und seine Stille war ein Zufluchtsort für Träume.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-thirdfield",'value': '<p>Die antiken Ruinen flüsterten Geschichten von längst vergangenen Zivilisationen, ihre bröckelnden Fassaden waren vom Echo der Geschichte geprägt. Steinsäulen standen wie Wächter da, Überbleibsel einer Pracht, die die Zeit verloren hatte. Inmitten der Ruinen konnte man sich fast die geschäftigen Marktplätze, die Gesänge der Philosophen und die Inbrunst religiöser Zeremonien vorstellen. Als der Wind durch die Überreste fegte und den Staub von Jahrhunderten trug, überkamen die Besucher ein Gefühl der Ehrfurcht und Demut und erinnerten sie an die Vergänglichkeit menschlicher Bemühungen und das bleibende Erbe, das Generationen zum Nachdenken hinterlassen haben.</p>'},

];

const spanishContent = [
  {"functionName":"val","id": "#title",'value': 'El futuro de la realidad aumentada'},
  {"functionName":"val","id": "#fields-shortDescription",'value': '<p>Anuncios personalizados en todas partes que mires</p>'},
  {"functionName":"val","id": "#fields-heading",'value': 'Tu iPhone ya no es una forma de ocultarte'},
  {"functionName":"val","id": "#fields-subheading",'value': 'Pero ahora es una manera de conectarse con el mundo'},
  {"functionName":"text","id": ".matrixblock:eq(0) textarea",'value': '<p>Cuando estás viendo el mundo a través de una pantalla, olvidas lo que es real y lo que no. Esto crea algunas oportunidades emocionantes para <br /><br />advertisers.Imagine este escenario: estás caminando a una cafetería y escucha una de tus canciones favoritas de tus días de la universidad. Ves para ver un coche que viene por la calle, y el conductor parece una versión más joven de ti mismo.<br /><br /> Él te da el más ligero guiño a medida que él pasa, y trae de vuelta recuerdos cálidos de tu joven <br /><br />passes, cuando usted ordena su café, usted ve un anuncio para el coche proyectado en su taza. Si quieres hacer una unidad de prueba, solo haz clic en \'sí\' y el coche vendrá a recogerte.<br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(1) input[type=\"text\"]",'value': 'Ves para ver un coche que viene por la calle, y el conductor parece una versión más joven de ti mismo.'},
  {"functionName":"val","id": ".matrixblock:eq(3) input[type=\"text\"]",'value': 'Un negocio de gente a gente'},
  {"functionName":"val","id": ".matrixblock:eq(5) textarea",'value': '<p>Cada persona quiere una versión ligeramente diferente de la realidad. Ahora pueden conseguirlo.<br /><br /><br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(0)",'value': 'La realidad aumentada ha sonado durante mucho tiempo como un concepto futurista salvaje, pero la tecnología ha estado en realidad durante años.'},
  {"functionName":"val","id": ".matrixblock:eq(6) input[type=\"text\"]:eq(1)",'value': 'Este es solo el Principio: Charlie Roths, Developers.Google'},
  {"functionName":"val","id": ".matrixblock:eq(7) input[type=\"text\"]",'value': '¿Qué es Happy Lager haciendo al respecto?'},
  {"functionName":"val","id": ".matrixblock:eq(8) textarea",'value': '<p>Cuando usted bebe nuestra cerveza, usamos la IA para evaluar su estado emocional, y usar un algoritmo patentado para generar un ambiente artificial que proporciona la state, la visualización y la estimulación auditiva que usted desea. <br /><br />Olvídate del mundo real a medida que soplamos el olor de la canela de tu madre pasa tu rostro.<br /><br /> Sumérgete en tu silla como Dean Martin canta jazz relajante <br /><br />standards.Play Candy Smash en impresionante resolución 8k, con solo un anuncio ocasional para extender tu experiencia de visualización.<br /></p>'},
  {"functionName":"val","id": ".matrixblock:eq(10) input[type=\"text\"]",'value': 'Este es solo el Principio'},
  {"functionName":"val","id": ".matrixblock:eq(11) textarea",'value': '<p>El mundo real tiene límites prácticos para los anunciantes. El mundo aumentado solo está limitado por tu presupuesto de diseño y valores de producción.</p>'},

  // NEO
  {"functionName":"val","type": "nested", "element": "input", "id": ".ni_blocks:eq(0) input",'value': 'El sol descendio lentamente detrás de las montañas, arrojando un cálido resplandor dorado sobre el tranquilo lago y el paisaje que lo rodea.'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".ni_blocks:eq(0) textarea",'value': '<p>El viejo roble, con sus ramas retorcidas que se extendían como dedos antiguos, era un testigo silencioso del paso del tiempo. Cada año, a medida que cambiaban las estaciones, mudaba sus hojas, solo para renacer en una vibrante explosión de verde cuando llegaba la primavera. Generaciones de pájaros anidaron en su dosel, sus cantos llenaron el aire con melodías de vida y renovación. Debajo de su sombra protectora, los niños jugaban, sus risas se mezclaban con el susurro de las hojas. Mientras el sol se ponía, proyectando largas sombras sobre el prado, el árbol se erguía alto, un centinela de la resistencia y la belleza perdurable de la naturaleza.</p>'},
  {"functionName":"val","type": "nested", "element": "input", "id": ".matrixblock:eq(12) input[type=\"text\"]",'value': 'El estudiante diligente leyó cuidadosamente el desafiante libro de texto, absorbiendo conocimientos para sobresalir en su proximo examen.'},
  {"functionName":"val","type": "nested", "element": "textarea", "id": ".matrixblock:eq(13) textarea",'value': '<p>La bulliciosa metrópolis bullía de vida, sus calles rebosaban de gente de todos los ámbitos de la vida. Los rascacielos perforaban el cielo, sus fachadas de vidrio reflejaban las vibrantes luces de la ciudad, mientras los taxis serpenteaban entre el tráfico, sus bocinas sonando con impaciencia. Los artistas callejeros entretuvieron a los transeúntes y sus talentos cautivaron al público con música, danza y magia. Los cafés rebosaban de conversaciones, el aroma del café recién hecho se mezclaba con el tentador aroma de las cocinas internacionales. En medio del caos, la ciudad emanaba una energía magnética, un crisol de culturas y sueños, donde se desarrollaban historias y se perseguían sueños contra viento y marea.<br /></p>'},

  // SuperTable Fields
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-firstfield",'value': 'Las vibrantes flores florecieron con gracia, llenando el aire con una deliciosa fragancia que despertó los sentidos y trajo alegría a todos.'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-secondfield",'value': '<p>La vasta extensión del desierto se extendía ante ellos, un paisaje árido de arenas movedizas y horizontes infinitos. Con cada paso, los granos de arena susurraban bajo sus pies, llevados por el viento en una danza siempre cambiante. El sol abrasador caía sobre ellos, sus rayos quemaban su piel, mientras los espejismos brillaban en la distancia, provocando sus sentidos con ilusiones de agua y oasis. Sin embargo, en medio de la dureza, emergió una belleza tranquila: los delicados patrones grabados por el viento, la resistencia de la flora del desierto y el impresionante espectáculo de las estrellas que iluminan el cielo nocturno.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2619-fields-thirdfield",'value': '<p>La gran sala de conciertos se alzaba con majestuosa elegancia, su arquitectura ornamentada era un testimonio de la artesanía y la expresión artística. Cuando se abrieron las puertas, la anticipación llenó el aire, mezclándose con los murmullos de la audiencia. La orquesta afinó sus instrumentos, las cuerdas resonaron con vibraciones armoniosas y el director levantó la batuta. Se desarrolló una sinfonía, las melodías se entrelazaron y los crescendos crecieron, llevando a los oyentes a un viaje emocional. Desde las inquietantes notas de un solo de violín hasta los estruendosos estruendos de la percusión, la música trascendió barreras, transportando almas a reinos donde las palabras se volvieron innecesarias.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-firstfield",'value': 'El sol se hundió en el horizonte, pintando el cielo en tonos de naranja y rosa, creando un espectáculo impresionante.'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-secondfield",'value': '<p>Un lago sereno ubicado entre montañas majestuosas, su superficie reluciente como un espejo que refleja el cielo. El silencio abrazó los alrededores, roto solo por el suave chapoteo del agua contra la orilla y el canto ocasional de un pájaro lejano. Los árboles se erguían como centinelas a lo largo de las orillas, su vibrante follaje reflejaba los colores del otoño. En este tranquilo oasis, el tiempo pareció ralentizarse, lo que permitió que las almas cansadas encontraran consuelo y se reconectaran con los ritmos de la naturaleza. Mientras el sol se hundía debajo de los picos, pintando el cielo en tonos naranja y púrpura, el lago abrazó la noche, su quietud un santuario para los sueños.</p>'},
  {"functionName":"val","type": "nested", "id": "#fields-superTableField-blocks-2620-fields-thirdfield",'value': '<p>Las antiguas ruinas susurraban historias de civilizaciones desaparecidas hace mucho tiempo, sus fachadas desmoronadas grabadas con los ecos de la historia. Los pilares de piedra se erguían como centinelas, restos de una grandeza que el tiempo había erosionado. En medio de las ruinas, uno casi podía imaginar los bulliciosos mercados, los cantos de los filósofos y el fervor de las ceremonias religiosas. A medida que el viento barría los restos, arrastrando el polvo de las eras, una sensación de asombro y humildad se apoderó de los visitantes, recordándoles la impermanencia de los esfuerzos humanos y el legado perdurable dejado atrás para que las generaciones reflexionen.</p>'},
];

const translations = {
  es: spanishContent,
  de: germanContent,
  uk: ukrainianContent
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
