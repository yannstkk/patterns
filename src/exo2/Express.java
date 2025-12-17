package src.exo2;

public class Express extends Decorator{

    public Express(Commande commande) {
        super(commande);
    }

    @Override
    public int calculatePrice() {
        return super.calculatePrice() + 5;
    }

    @Override
    void pay() {
    System.out.println("le client a payee : "+calculatePrice());

    }
    
}
