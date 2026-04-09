<template>
  <div class="landing-page d-flex align-center">
    <v-container class="py-16">
      <v-row align="center" justify="center">
        <v-col cols="12" lg="10">
          <v-sheet class="landing-sheet pa-8 pa-md-12" rounded="xl">
            <v-row align="center" class="ga-8">
              <v-col cols="12" md="7">
                <div class="text-overline mb-3">Gym SaaS MVP</div>
                <h1 class="text-h2 font-weight-bold mb-4">
                  Gym operations in one admin workspace
                </h1>
                <p class="text-h6 text-medium-emphasis mb-8">
                  Manage tenants, branches, members, plans, and subscriptions
                  from a single back office. For this MVP, the dashboard is the
                  main authenticated workspace.
                </p>

                <div class="d-flex flex-wrap ga-4 mb-8">
                  <v-btn color="primary" size="large" to="/dashboard"
                    >Open Dashboard</v-btn
                  >
                  <v-btn variant="outlined" size="large" to="/members"
                    >View Members</v-btn
                  >
                </div>

                <v-row>
                  <v-col cols="12" sm="6">
                    <v-card variant="tonal" rounded="lg">
                      <v-card-text>
                        <div class="text-subtitle-2 mb-1">
                          Suggested landing page
                        </div>
                        <div class="text-body-1">
                          Use this page as the public entry, then keep
                          `/dashboard` for the internal admin app.
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-card variant="tonal" rounded="lg">
                      <v-card-text>
                        <div class="text-subtitle-2 mb-1">Seeded accounts</div>
                        <div class="text-body-2">superadmin@gymsaas.local</div>
                        <div class="text-body-2">admin@demofitness.local</div>
                        <div class="text-body-2 mt-1">Password: password</div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-col>

              <v-col cols="12" md="5">
                <v-card class="pa-6" rounded="xl" elevation="8">
                  <div class="text-subtitle-1 font-weight-bold mb-4">
                    Current MVP modules
                  </div>
                  <v-list density="comfortable">
                    <v-list-item title="Tenant and branch management" />
                    <v-list-item title="Member records" />
                    <v-list-item title="Membership plans" />
                    <v-list-item title="Subscriptions" />
                    <v-list-item title="Dashboard statistics" />
                  </v-list>
                </v-card>
              </v-col>
            </v-row>
          </v-sheet>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<style scoped>
.landing-page {
  min-height: 100vh;
  background:
    radial-gradient(
      circle at top left,
      rgba(46, 125, 50, 0.14),
      transparent 28%
    ),
    radial-gradient(
      circle at bottom right,
      rgba(15, 23, 42, 0.1),
      transparent 22%
    ),
    linear-gradient(180deg, #f7faf7 0%, #eef3ef 100%);
}

.landing-sheet {
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(15, 23, 42, 0.08);
}
</style>
