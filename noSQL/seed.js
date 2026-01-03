// This script populates the 'reviews' collection with sample data.

const { MongoClient } = require('mongodb');

// --- Configuration ---
const user = encodeURIComponent("admin_ecor!de");
const password = encodeURIComponent("mongoEcor!de2025");
const dbName = "ecoride";

const uri = `mongodb://${user}:${password}@localhost:27017/${dbName}?authSource=admin`;
const collectionName = "reviews";

// --- Sample Data (V2) ---
// This data is aligned with the trips and users from seed.sql (V2).
const reviewsToSeed = [
    {
        // This review corresponds to the issue raised by user 5 on trip 2 in seed.sql
        user_id: "5", // Corresponds to Paul Martin
        trip_id: "2", // Corresponds to the Lyon-Marseille trip
        comment: "Le conducteur a pris un itinéraire plus long que nécessaire."
    },
    {
        // Positive review for trip 1 from user 6 (Chloé)
        user_id: "6",
        trip_id: "1",
        comment: "Super voyage, très confortable et le conducteur était génial. Je recommande vivement !"
    },
    {
        // Neutral review for trip 1 from user 7 (Lucas)
        user_id: "7",
        trip_id: "1",
        comment: "Le voyage s'est bien passé, mais nous sommes arrivés un peu en retard."
    },
    {
        // Positive review for trip 2 from user 8 (Emma)
        user_id: "8",
        trip_id: "2",
        comment: "Conducteur très professionnel, la voiture était propre. Je me suis senti en sécurité."
    },
    {
        // Positive review for trip 4 from user 10 (Léa)
        user_id: "10",
        trip_id: "4",
        comment: "Trajet court et agréable jusqu'à Monaco. Parfait !"
    },
    {
        // Positive review for trip 5 from user 6 (Chloé)
        user_id: "6",
        trip_id: "5",
        comment: "Encore un super voyage avec cette plateforme. Toujours fiable."
    }
];

/**
 * Main seeding function.
 * Connects to MongoDB, clears the collection, and inserts new data.
 */
async function seedNoSQL() {
    const client = new MongoClient(uri);

    try {
        // Connect to the MongoDB server
        await client.connect();
        console.log("Connected successfully to MongoDB server.");

        const db = client.db();
        const collection = db.collection(collectionName);

        // Clear existing data to prevent duplicates on re-running the script
        await collection.deleteMany({});
        console.log(`Cleared existing documents from '${collectionName}' collection.`);

        // Insert the new documents
        const result = await collection.insertMany(reviewsToSeed);
        console.log(`${result.insertedCount} documents were inserted into the '${collectionName}' collection.`);

    } catch (err) {
        console.error("An error occurred during the NoSQL seeding process:", err);
    } finally {
        // Ensure that the client will close when you finish/error
        await client.close();
        console.log("MongoDB connection closed.");
    }
}

// --- How to run this script ---
// 1. Open this file and fill in your credentials.
// 2. Make sure you have Node.js installed.
// 3. Install the MongoDB driver: npm install mongodb
// 4. Run the script from your terminal: node noSQL/seed.js

// Execute the seeding function
seedNoSQL();
