function generateRaidModal() {
    var raidStr = '<form class="raid-modal" style="display:none;" title="' + i8ln('Submit a Raid Report') + '">'
    raidStr += '<input type="hidden" value="" id="raidModalGymId" name="gymId" autofocus>'
    raidStr += '<div class=" switch-container">' +
        generateRaidBossList() +
        '</div>' +
        '<div class="mon-name" style="display:none;"></div>' +
        '<div class="switch-container timer-cont" style="text-align:center;display:none">' +
        '<h5 class="timer-name" style="margin-bottom:0;"></h5>' +
        generateTimerLists() +
        '</div>' +
        '<button type="button" onclick="manualRaidData(event);" class="submitting-raid"><i class="fa fa-binoculars" style="margin-right:10px;"></i>' + i8ln('Submit Raid') + '</button>' +
        '<button type="button" onclick="$(\'.ui-dialog-content\').dialog(\'close\');" class="close-modal"><i class="fa fa-times" aria-hidden="true"></i></button>' +
        '</form>'
    return raidStr
}

function generateRaidBossList() {
    var boss = raidBossActive
    var data = '<div class="pokemon-list raid-submission">'
    data += '<input type="hidden" name="pokemonId" value="">'
    data += '<span class="pokemon-icon-sprite" data-value="egg_1" data-label="Level 1" onclick="pokemonRaidFilter(event);"><span class="egg_1 inner-bg" style="background: url(\'static/raids/egg_normal.png\');background-size:100%"></span><span class="egg-number">1</span></span>'
    data += '<span class="pokemon-icon-sprite" data-value="egg_2" data-label="Level 2" onclick="pokemonRaidFilter(event);"><span class="egg_2 inner-bg" style="background: url(\'static/raids/egg_normal.png\');background-size:100%"></span><span class="egg-number">2</span></span>'
    data += '<span class="pokemon-icon-sprite" data-value="egg_3" data-label="Level 3" onclick="pokemonRaidFilter(event);"><span class="egg_3 inner-bg" style="background: url(\'static/raids/egg_rare.png\');background-size:100%"></span><span class="egg-number">3</span></span>'
    data += '<span class="pokemon-icon-sprite" data-value="egg_4" data-label="Level 4" onclick="pokemonRaidFilter(event);"><span class="egg_4 inner-bg" style="background: url(\'static/raids/egg_rare.png\');background-size:100%"></span><span class="egg-number">4</span></span>'
    data += '<span class="pokemon-icon-sprite" data-value="egg_5" data-label="Level 5" onclick="pokemonRaidFilter(event);"><span class="egg_5 inner-bg" style="background: url(\'static/raids/egg_legendary.png\');background-size:100%"></span><span class="egg-number">5</span></span>'
    boss.forEach(function (element) {
        var j = Math.floor(element / 28)
        var b = element % 28
        if (b === 0) {
            b = 28
            j = j - 1
        }
        var k = b - 1
        var p = j * 48.25
        var a = k * 48.25
        data += '<span class="pokemon-icon-sprite" data-value="' + element + '" data-label="' + pokeList[element - 1].name + '" onclick="pokemonRaidFilter(event);"><span class="' + element + ' inner-bg" style="background-position:-' + a + 'px -' + p + 'px"></span></span>'
    })
    data += '</div>'
    return data
}

function generateTimerLists() {
    var html = '<select name="egg_time" class="egg_time" style="display:none;">' +
        '<option value="60" selected>1:00</option>' +
        '<option value="59">59</option>' +
        '<option value="58">58</option>' +
        '<option value="57">57</option>' +
        '<option value="56">56</option>' +
        '<option value="55">55</option>' +
        '<option value="54">54</option>' +
        '<option value="53">53</option>' +
        '<option value="52">52</option>' +
        '<option value="51">51</option>' +
        '<option value="50">50</option>' +
        '<option value="49">49</option>' +
        '<option value="48">48</option>' +
        '<option value="47">47</option>' +
        '<option value="46">46</option>' +
        '<option value="45">45</option>' +
        '<option value="44">44</option>' +
        '<option value="43">43</option>' +
        '<option value="42">42</option>' +
        '<option value="41">41</option>' +
        '<option value="40">40</option>' +
        '<option value="39">39</option>' +
        '<option value="38">38</option>' +
        '<option value="37">37</option>' +
        '<option value="36">36</option>' +
        '<option value="35">35</option>' +
        '<option value="34">34</option>' +
        '<option value="33">33</option>' +
        '<option value="32">32</option>' +
        '<option value="31">31</option>' +
        '<option value="30">30</option>' +
        '<option value="29">29</option>' +
        '<option value="28">28</option>' +
        '<option value="27">27</option>' +
        '<option value="26">26</option>' +
        '<option value="25">25</option>' +
        '<option value="24">24</option>' +
        '<option value="23">23</option>' +
        '<option value="22">22</option>' +
        '<option value="21">21</option>' +
        '<option value="20">20</option>' +
        '<option value="19">19</option>' +
        '<option value="18">18</option>' +
        '<option value="17">17</option>' +
        '<option value="16">16</option>' +
        '<option value="15">15</option>' +
        '<option value="14">14</option>' +
        '<option value="13">13</option>' +
        '<option value="12">12</option>' +
        '<option value="11">11</option>' +
        '<option value="10">10</option>' +
        '<option value="9">9</option>' +
        '<option value="8">8</option>' +
        '<option value="7">7</option>' +
        '<option value="6">6</option>' +
        '<option value="5">5</option>' +
        '<option value="4">4</option>' +
        '<option value="3">3</option>' +
        '<option value="2">2</option>' +
        '<option value="1">1</option>' +
        '</select>' +
        '<select name="mon_time" class="mon_time" style="display:none;">' +
        '<option value="45" selected>45</option>' +
        '<option value="44">44</option>' +
        '<option value="43">43</option>' +
        '<option value="42">42</option>' +
        '<option value="41">41</option>' +
        '<option value="40">40</option>' +
        '<option value="39">39</option>' +
        '<option value="38">38</option>' +
        '<option value="37">37</option>' +
        '<option value="36">36</option>' +
        '<option value="35">35</option>' +
        '<option value="34">34</option>' +
        '<option value="33">33</option>' +
        '<option value="32">32</option>' +
        '<option value="31">31</option>' +
        '<option value="30">30</option>' +
        '<option value="29">29</option>' +
        '<option value="28">28</option>' +
        '<option value="27">27</option>' +
        '<option value="26">26</option>' +
        '<option value="25">25</option>' +
        '<option value="24">24</option>' +
        '<option value="23">23</option>' +
        '<option value="22">22</option>' +
        '<option value="21">21</option>' +
        '<option value="20">20</option>' +
        '<option value="19">19</option>' +
        '<option value="18">18</option>' +
        '<option value="17">17</option>' +
        '<option value="16">16</option>' +
        '<option value="15">15</option>' +
        '<option value="14">14</option>' +
        '<option value="13">13</option>' +
        '<option value="12">12</option>' +
        '<option value="11">11</option>' +
        '<option value="10">10</option>' +
        '<option value="9">9</option>' +
        '<option value="8">8</option>' +
        '<option value="7">7</option>' +
        '<option value="6">6</option>' +
        '<option value="5">5</option>' +
        '<option value="4">4</option>' +
        '<option value="3">3</option>' +
        '<option value="2">2</option>' +
        '<option value="1">1</option>' +
        '</select>'
    return html
}
