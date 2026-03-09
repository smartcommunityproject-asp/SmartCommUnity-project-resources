<?php
/**
 * Plugin Name: Assessment 2.0
 * Description: Assessment of smartness for Smart Alps
 * Version: 1.0
 * Author: FERI
 */

 // Hook to initialize the plugin functionality
add_action('init', 'assessment_init');

function assessment_enqueue_assets() {
    wp_enqueue_script('jquery');
    wp_enqueue_style('assessment-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('search-map', plugins_url('js/map.js', __FILE__), array('jquery'), null, true);
    wp_enqueue_script('polyfill', 'https://cdnjs.cloudflare.com/polyfill/v3/polyfill.min.js', null, null, true);
    
    wp_enqueue_script('map', plugins_url('js/map.js', __FILE__), array('jquery'), null, true);
     wp_enqueue_script('progress-script', plugins_url('js/progress.js', __FILE__), array('jquery'), null, true);
        
    // $google_maps_api_key = 'AIzaSyAVMIFLd2SLtIYrrLQ8mr-FaidDIjYD0d0'; // Replace with your actual API key..old UM
      $google_maps_api_key = 'AIzaSyCSke7P24DnMfGV4egaGwIKriyLRP4U_kI'; //new urban UL -- tudi v good practices map
    wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key=$google_maps_api_key&libraries=places&callback=initMap", null, null, true);

}
add_action('wp_enqueue_scripts', 'assessment_enqueue_assets');

// Add async and defer attributes to the Google Maps script
function add_async_defer_attributes($tag, $handle) {
    if ('google-maps' === $handle) {
        return str_replace(' src', ' async defer src', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'add_async_defer_attributes', 10, 2);


// Initialize the shortcode for the form
function assessment_init() {
    add_shortcode('assessment_form', 'render_assessment_form');
}



// Function to render the form
function render_assessment_form() {
     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         
           error_log(print_r($_POST, true));
           
           echo '<pre>';
        print_r($_POST);
        echo '</pre>';

        // Perform any form processing here (e.g., saving to database)

        // Redirect to the loader page
        wp_redirect(home_url('/loader'));
        exit;
    }

    $current_user = wp_get_current_user();
    $userid = $current_user->ID;
    //echo $userid;


    ob_start();
    ?>
    <div id="assessment-form-container" class="custom-form-container">
        
    <div class="row p-2">
        <div class="col-lg-12">
            <div class="progress">
                <div class="progress-bar progress-bar active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="row p-2">
        <div class="col-lg-12 pl-2">
            <form method="post" action="/loader" id="assessment-form">
                <fieldset id="fieldsetone">
                    <h2>Step 1: General information</h2>
                    <p>Filling this form will take you approximately 3-5 minutes.</p>

                     <div class="form-group">
                        <label for="aa-country" class="required">Country:</label>
                        <select name="a-country" id="aa-country" required class="form-control">
                            <option value="at">Austria</option>
                            <option value="de">Germany</option>
                            <option value="si" selected>Slovenia</option>
                            <option value="fr">France</option>
                            <option value="ch">Switzerland</option>
                            <option value="it">Italy</option>
                        </select>
                    </div>
                    
                    <input type="hidden" id="accesscode" name="accesscode" value="1913c1d7-42ea-4502-8017-fa5f97a13336">


                    <div id="pac-card">
                        <label for="a-address" class="required">Village name:</label><br>
                        <input id="pac-input" class="controls" name="a-address" type="text" placeholder="Enter a location">
                    </div>
                    <div id="map" style="height: 500px;"></div>
                    <input type="hidden" class="form-control " id="a-name" name="a-name" placeholder="Name">

                   <br>

                    <div class="form-group">
                        <label for="a-kind" class="required">Kind:</label>
                        <select name="a-kind" id="a-kind" required class="form-control">
                            <option hidden disabled selected value>Choose an option</option>
                            <option value="city">City</option>
                            <option value="village">Village</option>
                            <option value="municipality">Municipality</option>
                            <option value="local area">Local Area</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="a-inhabitants" class="required">Inhabitants:</label>
                        <select name="a-inhabitants" id="a-inhabitants" required class="form-control">
                            <option hidden disabled selected value>Choose an option</option>
                            <option value="hundred">Up to 100</option>
                            <option value="thousand">Up to 1,000</option>
                            <option value="ten-thousand">Up to 10,000</option>
                            <option value="fifty-thousand">Up to 50,000</option>
                            <option value="hundred-thousand">Up to 100,000</option>
                            <option value="hundred-fifty-thousand">Up to 150,000</option>
                            <option value="hundred-fifty-thousand-plus">More than 150,000</option>
                        </select>
                    </div>

                     <div class="form-group">
                            <label for="a-age" class="required">Assessor Age:</label>
                            <select name="a-age" id="a-age" class="form-control" required>
                                <option hidden disabled selected value>Choose an option</option>
                                <option value="youth">Youth</option>
                                <option value="elderly">Elderly</option>
                                <option value="students">Students</option>
                                <option value="active-working-people">Active working people</option>
                            </select>
                        </div>

                        
                        <div class="form-group">
                            <label for="a-type" class="required">Assessor Type:</label>
                            <select name="a-type" id="assessor-type" class="form-control" required>
                                <option hidden disabled selected value>Choose an option</option>
                                <option value="policy-maker">Policy Maker</option>
                                <option value="academia">Academia</option>
                                <option value="business">Business</option>
                                <option value="citizens">Citizens</option>
                            </select>
                        </div>
                        
                     <input type="button" name="next" class="next btn btn-info" id="next1" value="Next" onclick="getElementsCheck()" />
                </fieldset>
                
                <fieldset id="fieldsettwo" style="display: none;">
                    <h2>Step 2: Setting priorities</h2>
                    <p class="required">Please order the areas below by priority.</p>
                    <?php
                    $priorityArray = array("peopleRange", "governanceRange", "livingRange", "enviromentRange", "economyRange", "mobilityRange");
                    $varArray = array($old_smart_people, $old_smart_governance, $old_smart_living, $old_smart_environment, $old_smart_economy, $old_smart_mobility);
                    
                    $translatePriorityArray = array(
                            "peopleRange" => "Smartness of people priority level:",
                            "governanceRange" => "Smartness of governance priority level:",
                            "livingRange" => "Smartness of living priority level:",
                            "enviromentRange" => "Smartness of enviroment priority level:",
                            "economyRange" => "Smartness of economy priority level:",
                            "mobilityRange" => "Smartness of mobility priority level:",
                    );
            
                    $translateHelpArray = array(
                            'Smart People measures the participation of local citizens to the job market, the decision-making and the involvement in associations, and the education level of people. Examples of indicators include the number of associations, policies for promoting equal opportunities, level of schooling, overall employment, degree of political engagement, etc.',
                            'Smart Governance is related to the level of smartness of the governance systems, the penetration of green public procurement, egovernance, facilities to networking. Some examples of indicators include the number of electric cars used, the convenience of recycling policies, energy policies, etc.',
                            'Smart People is related to the quantity and quality of services to the population in the area, and the degree of satisfaction in them. Examples of indicators include the level of criminality, the level of general services such as banks, post offices, and so on, the quality health care and social care services, as well as the quality and quantity of services to the elderly, etc.',
                            'Smart Environment involves measuring the quality of the environment in terms of air, water, and soil. Examples of indicators include the air quality, level of recycling, percentage of natural spaces in the overall area, etc.',
                            'Smart Economy is measured in terms of the presence of creative and innovative enterprises and business models in the area, level of employment and unemployment, level of economic attractiveness, penetration of ICT in the local economic system. Examples of indicators include the number and density of certified enterprises, number of young and women-led enterprises, the rate of business creation, the number of patents, etc.',
                            'Smart Mobility is related to the quantity and quality of sustainable transport and mobility systems in the village. Examples of indicators include the number of non-conventional-fuel cars being owned or used, the presence of limited-traffic zones, the level and sustainability of public transport, etc.'
                    );
                    ?>
                    
                     <div class="tabs">
                        <?php
                        $k = 1;
                        foreach ($priorityArray as $i => $key) {
                            $value = $translatePriorityArray[$key];
                            $valueHelp = $translateHelpArray[$i];
                            $priorityValue = $varArray[$i] !== null ? $varArray[$i] : '0';
                            ?>
                    
                            <div class="tab p-1">
                                <!-- Adjust label and output placement -->
                                <label class="tab-label" for="rd<?= $i; ?>">
                                    <?= $value; ?> <span style="color:red">*</span>
                                    <output id="rangevalueOut<?= $k; ?>" name="<?= $key; ?>"><?= $priorityValue; ?></output>
                                </label>
                    
                                <div class="tab-content">
                                    <?= $valueHelp; ?>
                                </div>
                    
                                <input class="range" id="rangevalue<?= $k; ?>" type="range" 
                                       min="1" max="10" value="<?= $priorityValue; ?>" 
                                       step="1" name="<?= $key; ?>"
                                       oninput="rangevalueOut<?= $k; ?>.value = rangevalue<?= $k; ?>.value" />
                            </div>
                    
                            <?php $k++; ?>
                        <?php } ?>
                    </div>


                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous" />
                        <input type="button" name="next" class="next btn btn-info" id="next1" value="Next" onclick="getElementsCheck()" />
                     </fieldset>
                     
                   <fieldset id="fieldsetthree" style="display: none;">
                        <h2>Step 3: Smart People</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate">
                                <div><span class="m-rotate">Strongly disagree</span></div>
                            </div>
                            <div class="col-lg-1 col-xs-1 col-rotate">
                                <div><span class="m-rotate">Disagree</span></div>
                            </div>
                            <div class="col-lg-1 col-xs-1 col-rotate">
                                <div><span class="m-rotate">Neutral</span></div>
                            </div>
                            <div class="col-lg-1 col-xs-1 col-rotate">
                                <div><span class="m-rotate">Agree</span></div>
                            </div>
                            <div class="col-lg-1 col-xs-1 col-rotate">
                                <div><span class="m-rotate">Strongly agree</span></div>
                            </div>
                        </div>
                    
                        <!-- Smart People Questions -->
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    1. My village is subject to depopulation and ageing. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="people-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer1 !== null && $old_answer1 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    2. My village has a high level of digital literacy. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="people-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer2 !== null && $old_answer2 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    3. People in my village are eager to participate in meetings or assemblies of public interest. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="people-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer3 !== null && $old_answer3 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    4. There are active citizen associations and organisations in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="people-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer4 !== null && $old_answer4 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="people-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>
                    
                    <fieldset id="fieldsetfour" style="display: none;">
                        <h2>Step 4: Smart Governance</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Neutral</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Agree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly agree</span></div></div>
                        </div>
                    
                        <!-- Smart Governance Questions -->
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    1. The local Public Authorities provide web-based administrative and fiscal services (e-government). <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="gov-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer5 !== null && $old_answer5 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    2. The local Public Authorities involve citizens in decision-making. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="gov-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer6 !== null && $old_answer6 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    3. The local Public Authorities facilitate partnerships with private enterprises. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="gov-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer7 !== null && $old_answer7 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    4. The local Public Authorities communicate (news, decisions, information) in smart ways to citizens and/or visitors. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="gov-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer8 !== null && $old_answer8 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="gov-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>
                    
                    
                    <fieldset id="fieldsetfive" style="display: none;">
                        <h2>Step 5: Smart Living</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Neutral</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Agree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly agree</span></div></div>
                        </div>
                    
                        <!-- Smart Living Questions -->
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 text-left col-xs-7 pb-2-sm required">
                                    1. I am satisfied with the level of health and social care services in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="living-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer9 !== null && $old_answer9 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    2. There is a satisfying coverage of internet connection in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="living-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer10 !== null && $old_answer10 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    3. My village is attractive from the point of view of basic services to the population (banks, post-offices, basic-good shops, bars & restaurants, pharmacies, educational services). <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="living-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer11 !== null && $old_answer11 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    4. Smart working (co-working spaces, tele-working) is possible in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="living-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer12 !== null && $old_answer12 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="living-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>
                    
                    <fieldset id="fieldsetsix" style="display: none;">
                        <h2>Step 6: Smart Environment</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Neutral</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Agree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly agree</span></div></div>
                        </div>
                    
                        <!-- Smart Environment Questions -->
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 text-left col-xs-7 pb-2-sm required">
                                    1. My village produces energy from renewable energy sources. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="env-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer13 !== null && $old_answer13 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 text-left col-xs-7 pb-2-sm required">
                                    2. My village uses energy produced from renewable energy sources. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="env-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer14 !== null && $old_answer14 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 text-left col-xs-7 pb-2-sm required">
                                    3. My village is working towards a zero waste economy. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="env-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer15 !== null && $old_answer15 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 text-left col-xs-7 pb-2-sm required">
                                    4. People in my village are aware of natural risks. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="env-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer16 !== null && $old_answer16 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="env-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>


                    <fieldset id="fieldsetseven" style="display: none;">
                        <h2>Step 7: Smart Economy</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Neutral</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Agree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly agree</span></div></div>
                        </div>
                    
                        <!-- Smart Economy Questions -->
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    1. There are apps/platforms in my village that inform about local tourist attractions and services and/or support the local economy (e.g., digital marketplace). <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="econ-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer17 !== null && $old_answer17 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    2. In my village, services for tourists make use of innovative and/or smart approaches. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="econ-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer18 !== null && $old_answer18 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    3. My village ensures that the local economy, including tourism, does not have a negative impact on the environment. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="econ-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer19 !== null && $old_answer19 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    4. Economic activities in my area have innovative approaches. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="econ-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer20 !== null && $old_answer20 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="econ-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>
                    
                    
                    <fieldset id="fieldseteight" style="display: none;">
                        <h2>Step 8: Smart Mobility</h2>
                        <p class="required">Please check whether the statements apply to your community.</p>
                    
                        <div class="row small bottom-align">
                            <div class="col-lg-7 col-xs-7"></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Disagree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Neutral</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Agree</span></div></div>
                            <div class="col-lg-1 col-xs-1 col-rotate"><div><span class="m-rotate">Strongly agree</span></div></div>
                        </div>
                    
                        <div class="form-group">
                            <!-- Question 1 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    1. I consider the offer of public transport adequate in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="mob-q1" type="radio" value="<?= $i; ?>" <?= ($old_answer21 !== null && $old_answer21 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                    
                            <!-- Question 2 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    2. There are solutions and services to share means of transport (car-sharing, car-pooling, etc.) in my village. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="mob-q2" type="radio" value="<?= $i; ?>" <?= ($old_answer22 !== null && $old_answer22 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 3 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    3. In my village, there is an integrated web-based transport platform for citizens and/or visitors to use. <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="mob-q3" type="radio" value="<?= $i; ?>" <?= ($old_answer23 !== null && $old_answer23 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                            <br>
                    
                            <!-- Question 4 -->
                            <div class="row text-center">
                                <div class="col-md-7 col-xs-7 text-left pb-2-sm required">
                                    4. In my village, there are multimodal transport infrastructures (train/bus station and car sharing; train/bus station and bike sharing, etc.). <span class="text-danger">*</span>
                                </div>
                                <?php for ($i = 0; $i < 5; $i++) { ?>
                                    <div class="col-lg-1 col-xs-1">
                                        <input name="mob-q4" type="radio" value="<?= $i; ?>" <?= ($old_answer24 !== null && $old_answer24 === $i) ? 'checked' : ''; ?>>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    
                        <!-- Comment Section -->
                        <div class="form-group">
                            <input type="text" class="form-control" name="mob-comment" placeholder="Comment">
                        </div>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="button" name="next" class="next btn btn-info" value="Next">
                    </fieldset>
                    
                    <fieldset id="fieldsetfinal" style="display: none;" class="mb-20">
                        <h2>Assemble and Get the Best Practices</h2>
                        <p class="required">
                            Thank you for filling out the assessment form. Based on your answers, we will assemble the areas your community excels in and the areas needing improvement. After checking the results, do not forget to go through the best practices from other towns that will be shown based on your results.
                        </p>
                    
                        <!-- Navigation Buttons -->
                        <input type="button" name="previous" class="previous btn btn-secondary" value="Previous">
                        <input type="submit" name="submit" class="submit btn btn-success" value="Submit">
                    </fieldset>




            </form>
        </div>
    </div>
       </div>
    <?php
    return ob_get_clean();
}