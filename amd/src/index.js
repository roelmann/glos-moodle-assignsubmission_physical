// Set the location select element as "required" if the user sets the assignment as a physical assignment.
define([], function() {
    function load() {
        document.getElementById("id_assignsubmission_physical_enabled").addEventListener("click", function() {
            if (document.getElementById("id_assignsubmission_physical_enabled").checked === true) {
                document.getElementById("id_location").setAttribute("required", true);
            } else {
                document.getElementById("id_location").removeAttribute("required");
            }
        }, false);
    }
    return {
        enhanceSettings: function() {
            load();
        },
    };
});
