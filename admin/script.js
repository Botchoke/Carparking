document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       DASHBOARD COUNTERS
    ========================================================= */

    const counters = document.querySelectorAll(".counter");

    counters.forEach(function (counter) {

        const target = Number(
            counter.getAttribute("data-target")
        );

        let count = 0;

        function updateCounter() {

            if (count < target) {
                count++;
                counter.innerText = count;
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }

        }

        updateCounter();

    });


    /* =========================================================
       SEARCH
    ========================================================= */

    const searchBar = document.getElementById("searchBar");

    if (searchBar) {

        searchBar.addEventListener("keyup", function () {

            const value = this.value
                .toLowerCase()
                .trim();

            const rows = document.querySelectorAll(
                "#vehicleTable tbody tr"
            );

            rows.forEach(function (row) {

                const plate = row.children[1]
                    ? row.children[1].textContent.toLowerCase()
                    : "";

                const owner = row.children[2]
                    ? row.children[2].textContent.toLowerCase()
                    : "";

                row.style.display =
                    plate.includes(value) ||
                    owner.includes(value)
                        ? ""
                        : "none";

            });

        });

    }

});