package src.exo2;


public class CommandeFactory {

    public static Commande creerCommande(String type) {
        return switch (type) {
            case "STANDARD" -> new Standard();
            case "NUMERIQUE" -> new Numerique();
            default -> throw new IllegalArgumentException("Type inconnu");
        };
    }
}
